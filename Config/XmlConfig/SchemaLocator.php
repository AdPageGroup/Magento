<?php declare(strict_types=1);

namespace Tagging\GTM\Config\XmlConfig;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;

class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * @param Reader $moduleReader
     */
    public function __construct(private readonly Reader $moduleReader)
    {
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getSchema()
    {
        return $this->getXsdPath();
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function getPerFileSchema()
    {
        return $this->getXsdPath();
    }

    private function getXsdPath(): string
    {
        return $this->moduleReader->getModuleDir(
                Dir::MODULE_ETC_DIR,
                'Tagging_GTM')
            . '/' . 'data_layer.xsd';
    }
}
