<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\TokenManagerInterface;

abstract class AbstractVoucherSeries extends \Pimcore\Model\DataObject\Concrete
{
    /**
     * @return \Pimcore\Model\DataObject\Fieldcollection
     */
    abstract public function getTokenSettings();

    /**
     * @return TokenManagerInterface|null
     */
    public function getTokenManager()
    {
        $items = $this->getTokenSettings();

        if ($items->get(0)) {
            // name of fieldcollection class
            $configuration = $items->get(0);

            return Factory::getInstance()->getTokenManager($configuration);
        }

        return null;
    }

    /**
     * @return array|bool
     */
    public function getExistingLengths()
    {
        $db = \Pimcore\Db::get();

        $query = '
            SELECT length, COUNT(*) AS count FROM ' . \Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token\Dao::TABLE_NAME . '
            WHERE voucherSeriesId = ?
            GROUP BY length';

        try {
            $lengths = $db->fetchAll($query, [$this->getId()]);

            $result = [];
            foreach ($lengths as $lengthEntry) {
                $result[$lengthEntry['length']] = $lengthEntry['count'];
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
}
