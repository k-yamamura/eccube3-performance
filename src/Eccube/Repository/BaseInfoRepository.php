<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Repository;

use Doctrine\ORM\EntityRepository;
use Eccube\Entity\BaseInfo;

/**
 * BaseInfoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BaseInfoRepository extends EntityRepository
{

    public $cacheKey = 'base_info';

    /**
     * get
     *
     * @param mixed $id The identifier.
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function get($id = 1)
    {

        $query = $this->createQueryBuilder('bi')
            ->where('bi.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->useResultCache(true, null, $this->cacheKey);

        return $query->getSingleResult();
    }

    /**
     * BaseInfoを保存
     *
     * @param BaseInfo $BaseInfo
     */
    public function save(BaseInfo $BaseInfo)
    {

        $em = $this->getEntityManager();

        $em->persist($BaseInfo);
        $em->flush($BaseInfo);

        $em->getConfiguration()->getResultCacheImpl()->delete($this->cacheKey);

    }
}
