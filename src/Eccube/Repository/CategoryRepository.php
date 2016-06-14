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
use Doctrine\ORM\Query;
use Eccube\Entity\Category;

/**
 * CategoryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CategoryRepository extends EntityRepository
{

    public $cacheKey = 'category';


    /**
     * 全カテゴリの合計を取得する.
     *
     * @return int 全カテゴリの合計数
     */
    public function getTotalCount()
    {
        $qb = $this
            ->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.del_flg = 0');
        $count = $qb->getQuery()
            ->getSingleScalarResult();

        return $count;
    }

    /**
     * カテゴリ一覧を取得する.
     *
     * 引数 $Parent を指定した場合は, 指定したカテゴリの子以下を取得する.
     *
     * @param \Eccube\Entity\Category|null $Parent 指定の親カテゴリ
     * @return \Eccube\Entity\Category[] カテゴリの配列
     */
    public function getList(Category $Parent = null)
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.rank', 'DESC');
        if ($Parent) {
            $qb->where('c.Parent = :Parent')->setParameter('Parent', $Parent);
        } else {
            $qb->where('c.Parent IS NULL');
        }
        $Categories = $qb->getQuery()
            ->useResultCache(true, null, $this->cacheKey)
            ->getResult();

        return $Categories;
    }


    /**
     * カテゴリ一覧を取得する.
     *
     * @return array
     */
    public function getCategories()
    {
        $query = $this->createQueryBuilder('c')
            ->getQuery()
            ->useResultCache(true, null, $this->cacheKey);

        return $query->getResult();
    }


    /**
     * カテゴリの順位を1上げる.
     *
     * @param  \Eccube\Entity\Category $Category カテゴリ
     * @return boolean 成功した場合 true
     */
    public function up(\Eccube\Entity\Category $Category)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $rank = $Category->getRank();
            $Parent = $Category->getParent();

            if ($Parent) {
                $CategoryUp = $this->createQueryBuilder('c')
                    ->where('c.rank > :rank AND c.Parent = :Parent')
                    ->setParameter('rank', $rank)
                    ->setParameter('Parent', $Parent)
                    ->orderBy('c.rank', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleResult();
            } else {
                $CategoryUp = $this->createQueryBuilder('c')
                    ->where('c.rank > :rank AND c.Parent IS NULL')
                    ->setParameter('rank', $rank)
                    ->orderBy('c.rank', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleResult();
            }

            $this_count = $Category->countBranches();
            $up_count = $CategoryUp->countBranches();

            $Category->calcChildrenRank($em, $up_count);
            $CategoryUp->calcChildrenRank($em, $this_count * -1);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }

        return true;
    }

    /**
     * カテゴリの順位を1下げる.
     *
     * @param  \Eccube\Entity\Category $Category カテゴリ
     * @return boolean 成功した場合 true
     */
    public function down(\Eccube\Entity\Category $Category)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $rank = $Category->getRank();
            $Parent = $Category->getParent();

            if ($Parent) {
                $CategoryDown = $this->createQueryBuilder('c')
                    ->where('c.rank < :rank AND c.Parent = :Parent')
                    ->setParameter('rank', $rank)
                    ->setParameter('Parent', $Parent)
                    ->orderBy('c.rank', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleResult();
            } else {
                $CategoryDown = $this->createQueryBuilder('c')
                    ->where('c.rank < :rank AND c.Parent IS NULL')
                    ->setParameter('rank', $rank)
                    ->orderBy('c.rank', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getSingleResult();
            }

            $this_count = $Category->countBranches();
            $down_count = $CategoryDown->countBranches();

            $Category->calcChildrenRank($em, $down_count * -1);
            $CategoryDown->calcChildrenRank($em, $this_count);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }

        return true;
    }

    /**
     * カテゴリを保存する.
     *
     * @param  \Eccube\Entity\Category $Category カテゴリ
     * @return boolean 成功した場合 true
     */
    public function save(\Eccube\Entity\Category $Category)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            if (!$Category->getId()) {
                $Parent = $Category->getParent();
                if ($Parent) {
                    $rank = $Parent->getRank() - 1;
                } else {
                    $rank = $this->createQueryBuilder('c')
                        ->select('MAX(c.rank)')
                        ->getQuery()
                        ->getSingleScalarResult();
                }
                if (!$rank) {
                    $rank = 0;
                }
                $Category->setRank($rank + 1);
                $Category->setDelFlg(0);

                $em->createQueryBuilder()
                    ->update('Eccube\Entity\Category', 'c')
                    ->set('c.rank', 'c.rank + 1')
                    ->where('c.rank > :rank')
                    ->setParameter('rank', $rank)
                    ->getQuery()
                    ->execute();
            }

            $em->persist($Category);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }

        return true;
    }

    /**
     * カテゴリを削除する.
     *
     * @param  \Eccube\Entity\Category $Category 削除対象のカテゴリ
     * @return boolean 成功した場合 true, 子カテゴリが存在する場合, 商品カテゴリが紐づいている場合は false
     */
    public function delete(\Eccube\Entity\Category $Category)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            if ($Category->getChildren()->count() > 0 || $Category->getProductCategories()->count() > 0) {
                throw new \Exception();
            }

            $rank = $Category->getRank();

            $em->createQueryBuilder()
                ->update('Eccube\Entity\Category', 'c')
                ->set('c.rank', 'c.rank - 1')
                ->where('c.rank > :rank')
                ->setParameter('rank', $rank)
                ->getQuery()
                ->execute();

            $Category->setDelFlg(1);
            $em->persist($Category);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }

        return true;
    }
}
