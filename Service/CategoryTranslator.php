<?php

namespace MageOS\AutomaticTranslation\Service;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\LocalizedException;
use MageOS\AutomaticTranslation\Api\CategoryTranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service as ServiceHelper;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface;
use Magento\Framework\DataObject;
use Magento\Catalog\Api\Data\ProductInterface;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Psr\Log\LoggerInterface as Logger;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use MageOS\AutomaticTranslation\Model\Config\Source\TextAttributes;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogUrlRewrite\Model\Products\AppendUrlRewritesToProducts;
use Exception;

/**
 * Class CategoryTranslator
 */
class CategoryTranslator implements CategoryTranslatorInterface
{
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;
    /**
     * @var ServiceHelper
     */
    protected ServiceHelper $serviceHelper;
    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;
    /**
     * @var CategoryResource
     */
    protected CategoryResource $categoryResource;
    /**
     * @var Gallery
     */
    protected Gallery $gallery;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var AppendUrlRewritesToProducts
     */
    protected AppendUrlRewritesToProducts $appendRewrites;
    /**
     * @var Logger
     */
    protected Logger $logger;

    const CATEGORY_TRANSLATABLE_ATTRIBUTES = [
        'name',
        'description',
//        'url_key',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'short_description4'
    ];

    /**
     * @param ModuleConfig $moduleConfig
     * @param ServiceHelper $serviceHelper
     * @param TranslatorInterface $translator
     * @param CategoryResource $categoryResource
     * @param Gallery $gallery
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        ServiceHelper $serviceHelper,
        TranslatorInterface $translator,
        CategoryResource $categoryResource,
        Gallery $gallery,
        StoreManagerInterface $storeManager,
        AppendUrlRewritesToProducts $appendRewrites,
        Logger $logger
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->translator = $translator;
        $this->categoryResource = $categoryResource;
        $this->gallery = $gallery;
        $this->storeManager = $storeManager;
        $this->appendRewrites = $appendRewrites;
        $this->logger = $logger;
    }

    /**
     * @param CategoryInterface $category
     * @param string $targetLanguage
     * @param string $sourceLanguage
     * @param string $storeName
     * @param int $storeId
     * @throws LocalizedException
     */
    public function translateCategory(
        CategoryInterface $category,
        string $targetLanguage,
        string $sourceLanguage,
        string $storeName = 'Default Store View',
        int $storeId = 0
    ): void {
        /** @var $category DataObject|ProductInterface */

        $category->setStoreId($storeId);
        foreach (self::CATEGORY_TRANSLATABLE_ATTRIBUTES as $attributeCode) {
            $textToTranslate = $category->getData($attributeCode);
            if (!empty($textToTranslate)) {
                try {
                    $parsedContent = $this->serviceHelper->parsePageBuilderHtmlBox($textToTranslate);

                    if (is_string($parsedContent)) {
                        $textTranslated = $this->translator->translate(
                            $textToTranslate,
                            $targetLanguage,
                            $sourceLanguage
                        );
                    } else {
                        $textToTranslate = html_entity_decode(htmlspecialchars_decode($textToTranslate));
                        $textTranslated = $textToTranslate;

                        foreach ($parsedContent as $parsedString) {
                            $parsedString["translation"] = $this->translator->translate(
                                $parsedString["source"],
                                $targetLanguage
                            );

                            $textTranslated = str_replace(
                                $parsedString["source"],
                                $parsedString["translation"],
                                $textTranslated
                            );
                        }

                        $textTranslated = $this->serviceHelper->encodePageBuilderHtmlBox($textTranslated);
                    }

                    if ($textToTranslate != $textTranslated) {
                        $category->setData($attributeCode, $textTranslated);
                        $this->categoryResource->saveAttribute($category, $attributeCode);

                        if ($this->moduleConfig->enableUrlRewrite($storeId) && $attributeCode === 'url_key') {
                            $storesToRewrite = [$this->storeManager->getStore($storeId)];
                            $this->appendRewrites->execute([$category], $storesToRewrite);
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->debug('Error when translating the category');
                    $this->logger->debug('Category ID: ' . $category->getId());
                    $this->logger->debug('Store: ' . $storeName . '(id ' . $storeId . ')');
                    $this->logger->debug('Attribute: ' . $attributeCode);
                    $this->logger->debug($e->getMessage());
                    $this->logger->debug('-------------------------');
                }
            }
        }

        $category->setData(AttributeProviderInterface::SKIP_TRANSLATION, true);
        try {
            $this->categoryResource->saveAttribute($category, AttributeProviderInterface::SKIP_TRANSLATION);
        } catch (Exception $e) {
            $this->logger->debug('Error when flagging category as "already translated"');
            $this->logger->debug('Category ID: ' . $category->getId());
            $this->logger->debug('Store: ' . $storeName . '(id ' . $storeId . ')');
            $this->logger->debug($e->getMessage());
            $this->logger->debug('-------------------------');
        }

        $category->setData(AttributeProviderInterface::LAST_TRANSLATION, date('Y-m-d H:i:s'));
        try {
            $this->categoryResource->saveAttribute($category, AttributeProviderInterface::LAST_TRANSLATION);
        } catch (Exception $e) {
            $this->logger->debug('Error when saving translation date and time');
            $this->logger->debug('Category ID: ' . $category->getId());
            $this->logger->debug('Store: ' . $storeName . '(id ' . $storeId . ')');
            $this->logger->debug($e->getMessage());
            $this->logger->debug('-------------------------');
        }
    }
}
