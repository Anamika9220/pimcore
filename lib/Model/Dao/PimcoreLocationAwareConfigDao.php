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

namespace Pimcore\Model\Dao;

use Pimcore\Config;

/**
 * @internal
 */
abstract class PimcoreLocationAwareConfigDao implements DaoInterface
{
    use DaoTrait;

    private static array $cache = [];

    protected ?string $settingsStoreScope = null;

    protected ?string $dataSource = null;

    private ?string $id = null;

    private Config\LocationAwareConfigRepository $locationAwareConfigRepository;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $params = func_get_arg(0);
        $this->settingsStoreScope = $params['settingsStoreScope'] ?? 'pimcore_config';

        if (!isset(self::$cache[$this->settingsStoreScope])) {
            // initialize runtime cache
            self::$cache[$this->settingsStoreScope] = [];
        }

        $this->locationAwareConfigRepository = new Config\LocationAwareConfigRepository(
            $params['containerConfig'] ?? [],
            $this->settingsStoreScope,
            $params['storageDirectory'] ?? null,
            $params['writeTargetEnvVariableName'] ?? null,
            $params['legacyConfigFile'] ?? null
        );
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     */
    protected function getDataByName(string $id)
    {
        $this->id = $id;

        if (isset(self::$cache[$this->settingsStoreScope][$id])) {
            return self::$cache[$this->settingsStoreScope][$id];
        }

        list($data, $this->dataSource) = $this->locationAwareConfigRepository->loadConfigByKey($id);

        self::$cache[$this->settingsStoreScope][$id] = $data;

        return $data;
    }

    /**
     * @return array
     */
    protected function loadIdList(): array
    {
        return $this->locationAwareConfigRepository->fetchAllKeys();
    }

    /**
     * @param string $id
     * @param array $data
     *
     * @throws \Exception
     */
    protected function saveData(string $id, $data)
    {
        $dao = $this;
        $this->locationAwareConfigRepository->saveConfig($id, $data, function ($id, $data) use ($dao) {
            return $dao->prepareDataStructureForYaml($id, $data);
        });
    }

    /**
     * Hook to prepare config data structure for yaml
     *
     * @param string $id
     * @param mixed $data
     *
     * @return mixed
     */
    protected function prepareDataStructureForYaml(string $id, $data)
    {
        return $data;
    }

    /**
     * @return string Can be either yaml (var/config/...) or "settings-store". defaults to "yaml"
     *
     * @throws \Exception
     */
    public function getWriteTarget(): string
    {
        return $this->locationAwareConfigRepository->getWriteTarget();
    }

    /**
     * @return bool
     */
    public function isWriteable(): ?bool
    {
        return $this->locationAwareConfigRepository->isWriteable($this->id, $this->dataSource);
    }

    /**
     * @param string $id
     *
     * @throws \Exception
     */
    protected function deleteData(string $id): void
    {
        $this->locationAwareConfigRepository->deleteData($id, $this->dataSource);
    }
}