<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * ActivityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ActivityRepository extends EntityRepository
{

    public function countDatesByInterval($dateFrom, $dateTo, $queryString = null) {
        $querySearchDQL = null;
        $querySearch = null;
        if($queryString) {
            $querySearch = $this->searchQueryToQueryDoctrine($queryString, $dateFrom, $dateTo);
            $querySearchDQL = ' AND a IN('.$querySearch->getDQL().')';
        }

        $dateFrom = clone $dateFrom;
        $dateFrom = $dateFrom->modify("+ 1 day");

        $query = $this->getEntityManager()
                    ->createQuery('
                          SELECT DATE(a.executedAt) as date, at.name, COUNT(a) as total
                          FROM AppBundle:Activity a
                          LEFT JOIN a.tags at
                          WHERE a.executedAt >= :date_to AND a.executedAt <= :date_from'.$querySearchDQL.'
                          GROUP BY date, at.name
                          ORDER BY a.executedAt ASC
                      ')
                    ->setParameter('date_from', $dateFrom)
                    ->setParameter('date_to', $dateTo);

        if($querySearch) {
            foreach($querySearch->getParameters() as $p) {
                $query->setParameter($p->getName(), $p->getValue());
            }
        }

        return $query->getScalarResult();
    }

    public function findByDatesIntervalByDays($dateFrom, $dateTo, $queryString = null, $nbDaysMax = null) {
        $querySearchDQL = null;
        $querySearch = null;
        if($queryString) {
            $querySearch = $this->searchQueryToQueryDoctrine($queryString, $dateFrom, $dateTo);
            $querySearchDQL = ' AND a IN('.$querySearch->getDQL().')';
        }

        $dateFrom = $dateFrom->modify("+ 1 day");

        $query = $this->getEntityManager()
                    ->createQuery('
                          SELECT DATE(a.executedAt) as date
                          FROM AppBundle:Activity a
                          WHERE a.executedAt >= :date_to AND a.executedAt <= :date_from'.$querySearchDQL.'
                          GROUP BY date
                          ORDER BY a.executedAt DESC
                      ')
                    ->setParameter('date_from', $dateFrom)
                    ->setParameter('date_to', $dateTo);

        if($nbDaysMax) {
            $query->setMaxResults($nbDaysMax);
        }

        if($querySearch) {
            foreach($querySearch->getParameters() as $p) {
                $query->setParameter($p->getName(), $p->getValue());
            }
        }

        $dates = $query->getScalarResult();

        if(!count($dates)) {

            return array();
        }

        $dateTo = new \DateTime($dates[count($dates) - 1]['date']);
        $dateTo = $dateTo->modify("+4 hours");
        $dateFrom = $dateFrom->modify("+4 hours");

        if($queryString) {
            $querySearch = $this->searchQueryToQueryDoctrine($queryString, $dateFrom, $dateTo);
            $querySearchDQL = ' AND a IN('.$querySearch->getDQL().')';
        }

        $query = $this->getEntityManager()
                    ->createQuery('
                    SELECT a, aa, at
                    FROM AppBundle:Activity a
                    LEFT JOIN a.attributes aa
                    LEFT JOIN a.tags at
                    WHERE a.executedAt >= :date_to AND a.executedAt <= :date_from'.$querySearchDQL.'
                    ORDER BY a.executedAt DESC
                      ')
                    ->setParameter('date_from', $dateFrom)
                    ->setParameter('date_to', $dateTo);

        if($querySearch) {
            foreach($querySearch->getParameters() as $p) {
                $query->setParameter($p->getName(), $p->getValue());
            }
        }

        return $query->getResult();
    }

    public function findByFilter($filter) {
        $querySearch = $this->searchQueryToQueryDoctrine($filter->getQuery());

        $query = $this->getEntityManager()
                    ->createQuery('
                          SELECT a
                          FROM AppBundle:Activity a
                          WHERE a NOT IN (SELECT asub FROM AppBundle:Activity asub JOIN asub.tags tsub WITH tsub = :tag)
                            AND a IN('.$querySearch->getDQL().')
                          ORDER BY a.executedAt DESC
                     ')
                    ->setParameter('tag', $filter->getTag());

        foreach($querySearch->getParameters() as $p) {
            $query->setParameter($p->getName(), $p->getValue());
        }

        return $query->getResult();
    }

    public function normalizeQuery($query) {
        $queryNormalized = null;
        $defaultOperator = " AND ";
        $operator = $defaultOperator;

        $mainParts = preg_split("/:/", $query);
        foreach($mainParts as $mainPart) {
            $parts = str_getcsv($mainPart, " ", '"');
            foreach($parts as $part) {
                if(!$part) {
                    continue;
                }
                if(in_array($part, array("OR", "AND"))) {
                    $operator = " ".$part." ";
                    continue;
                }

                if($queryNormalized) {
                    $queryNormalized .= $operator;
                    $operator = $defaultOperator;
                }

                $queryNormalized .= $part;
            }
            $operator = ":";
        }

        return $queryNormalized;
    }

    public function queryToArray($searchQuery) {
        $queryNormalized = $this->normalizeQuery($searchQuery);
        $terms = preg_split("/ (AND|OR) /", $queryNormalized);
        $params = array();
        foreach($terms as $term) {
            $param = explode(":", trim($term));
            if(count($param) < 2) {
              $param[1] = $param[0];
              $param[0] = '*';
            }
            array_push($params, $param);
        }

        return $params;
    }

    public function queryToHierarchy($searchQuery) {
        $queryNormalized = $this->normalizeQuery($searchQuery);
        $operators = preg_match_all("/ (AND|OR) /", $queryNormalized, $matches);

        $operators = array();

        foreach($matches[1] as $operator) {
            $operators[] = strtolower($operator);
        }

        return $operators;
    }

    public function searchQueryToQueryDoctrine($searchQuery, $dateFrom = null, $dateTo = null) {
        $params = $this->queryToArray($searchQuery);
        $operators = $this->queryToHierarchy($searchQuery);

        $query = $this->getEntityManager()->createQueryBuilder()
                                 ->select('aq')
                                 ->from('AppBundle:Activity', 'aq');

        $queriesFilter = array();
        foreach($params as $key => $param) {
            $name = $param[0];
            $value = str_replace('*', '%', $param[1]);

            if($name == 'title' || $name == 'content') {
                $queriesFilter[] = $query->expr()->like('(aq.'.$name, ':q'.$key.'value)');
                $query->setParameter('q'.$key.'value', $value);
            } elseif($name == 'tag') {
                $query
                    ->leftJoin('aq.tags', 'aqt'.$key)
                    ->setParameter('q'.$key.'value', $value);

                $queriesFilter[] = $query->expr()->like('(aqt'.$key.'.name', ':q'.$key.'value)');
            } elseif($name == "*") {
                $keyJoin = uniqid();

                $query
                    ->leftJoin('aq.attributes', "aqa".$keyJoin)
                    ->leftJoin('aq.tags', 'aqt'.$keyJoin)
                    ->setParameter(':q'.$key.'value', "%".$value."%");

                $queriesFilter[] = $query->expr()->orX(
                        $query->expr()->like('aq.title', ':q'.$key.'value'),
                        $query->expr()->like('aq.content', ':q'.$key.'value'),
                        $query->expr()->like("aqa".$keyJoin.'.value', ':q'.$key.'value'),
                        $query->expr()->like("aqt".$keyJoin.'.name', ':q'.$key.'value')
                    );
            } else {
                $queriesFilter[] = $query->expr()->andX(
                    $query->expr()->like('aqa'.$key.'.value', ':q'.$key.'value'),
                    $query->expr()->like('aqa'.$key.'.name', ':q'.$key.'name')
                );
                $query->leftJoin('aq.attributes', 'aqa'.$key)
                  ->setParameter('q'.$key.'name', $name)
                  ->setParameter('q'.$key.'value', $value);
            }
        }

        $query->andWhere(call_user_func_array(array($query->expr(), "andX"), $queriesFilter));
        $whereDQLOrigin = $query->getDQLPart("where");

        $whereDQLOrigin = preg_replace("/(^\(|\)$)/", "", $whereDQLOrigin);
        $whereParts = preg_split("/\) AND \(/", $whereDQLOrigin);

        $whereDQL = "";
        foreach($whereParts as $index => $wherePart) {
            $whereDQL .= "(".trim($wherePart).")";
            if(isset($operators[$index])) {
                $whereDQL .= " ".trim(strtoupper($operators[$index]))." ";
            }
        }
        if($dateTo && $dateFrom) {
            $whereDQLDate = "aq.executedAt >= :date_to AND aq.executedAt <= :date_from";
            $query->setParameter('date_from', $dateFrom)
                  ->setParameter('date_to', $dateTo);
        }

        if($whereDQLDate && $whereDQL) {
            $whereDQL = "(".$whereDQLDate .") AND (". $whereDQL.")";
        } else {
            $whereDQL = $whereDQLDate;
        }
        $query->add("where", $whereDQL);

        return $query;
    }
}
