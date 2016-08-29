<?php

namespace AppBundle\Importer\Github;

use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Importer\Importer;
use AppBundle\Entity\Activity;
use AppBundle\Entity\ActivityAttribute;
use AppBundle\Entity\Source;

class GithubImporter extends Importer
{
    public function getName() {

        return 'Github';
    }

    public function run(Source $source, OutputInterface $output, $dryrun = false, $checkExist = true, $limit = false) {

        $output->writeln(sprintf("<comment>Started import git commit on github %s</comment>", $source->getSource()));

        $user = preg_replace("|^http[s]*://github.com/|", "", $source->getSource());

        $fromDate = null;

        if(isset($source->getUpdateParam()['date'])) {
            $fromDate = (new \DateTime($source->getUpdateParam()['date']))->modify("-1 day")->format('c');
        }

        $events = array();
        for($i = 1; $i < 10; $i++) {
            $params = array('http'=> array('user_agent' => 'Mon activité'));
            //$params['http']['header'] = "Authorization: Basic " . base64_encode("user:password")));
            $eventsPage = json_decode(file_get_contents("https://api.github.com/users/".$user."/events?page=".$i, false, stream_context_create($params)));
            $finish = false;
            foreach($eventsPage as $event) {
                if($event->created_at < $fromDate) {
                    $finish = true;
                    break;
                }
                $events[] = $event;
            }
            if($finish) {
                break;
            }
        }

        $fromDate = new \DateTime($events[count($events) - 1]->created_at);
        $fromDate = $fromDate->modify('-7 days')->format('c');

        $repos = array();

        foreach($events as $key => $event) {
            if(!isset($event->payload->ref)) {
                continue;
            }
            $repo = explode("/", $event->repo->name);
            $key = $event->repo->name.$event->payload->ref;
            $repos[$key] = array(
                "repoUser" => $repo[0],
                "repoName" => $repo[1],
                "sha" => $event->payload->ref,
                "user" => $user,
                "since" => $fromDate,
            );
        }

        $client = new \Github\Client();
        //$client->authenticate("user", "password");

        $commits = array();
        foreach($repos as $repo) {
            try{
                $paginator = new \Github\ResultPager($client);
                $apiRepo = $client->api('repo');
                $commitsRepo = $paginator->fetchAll($apiRepo->commits(), 'all',
                    array($repo['repoUser'],
                          $repo['repoName'],
                          array('sha' => $repo['sha'], 'author' => $repo['user'], 'since' => $repo['since'])
                    )
                );
            } catch(\Github\Exception\RuntimeException $exception) {
                continue;
            }

            foreach($commitsRepo as $key => $commit) {
                $commitsRepo[$key]['repository'] = $repo['repoName'];
            }

            $commits = array_merge($commits, $commitsRepo);
        }

        $nb = 0;

        foreach($commits as $commit) {
            try {
                $activity = new Activity();
                $date = new \DateTime($commit["commit"]["author"]["date"]);
                $date = $date->modify("+2 hours");
                $activity->setExecutedAt($date);

                $activity->setTitle($commit["commit"]["message"]);

                $type = new ActivityAttribute();
                $type->setName("Type");
                $type->setValue("Commit");

                $repository = new ActivityAttribute();
                $repository->setName("Repository");
                $repository->setValue($commit['repository']);

                $author = new ActivityAttribute();
                $author->setName("Author");
                $author->setValue($commit["commit"]["author"]["email"]);

                $activity->addAttribute($type);
                $activity->addAttribute($repository);
                $activity->addAttribute($author);

                $this->am->addFromEntity($activity, $checkExist);

                $this->em->persist($type);
                $this->em->persist($repository);
                $this->em->persist($author);
                $this->em->persist($activity);

                if(!$dryrun) {
                    $this->em->flush($activity);
                }

                $nb++;

                if($output->isVerbose()) {
                    $output->writeln(sprintf("<info>Imported</info> %s", $activity->getTitle()));
                }

                if($limit && $nb > $limit) {
                    break;
                }
            } catch (\Exception $e) {
                if($output->isVerbose()) {
                    $output->writeln(sprintf("<error>%s</error> %s", $e->getMessage(), $activity->getTitle()));
                }
            }
        }

        $source->setUpdateParam(array('date' => date('Y-m-d')));

        if(!$dryrun) {
            $this->em->persist($source);
            $this->em->flush();
        }

        $output->writeln(sprintf("<info>%s new activity imported</info>", $nb));
    }

    public function getRootDir() {

        return dirname(__FILE__);
    }

    public function check(Source $source) {
        parent::check($source);

        if(!preg_match("|^http[s]*://github.com/|", $source->getSource())) {
            throw new \Exception(sprintf("Not github profile", $source->getSource()));
        }
    }

}
