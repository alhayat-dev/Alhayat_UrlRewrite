<?php
/**
 * @author Alhayat MagentDev
 * @copyriht Copyright (c) 2019 Eguana {http://alhayatmagentdev.com}
 * Created by PhpStorm
 * User: mudasser
 * Date: 30/10/19
 * Time: 10:28 PM
 */

namespace Alhayat\UrlRewrite\Console\Command;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGeneratorFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductRebuildUrlRewrite extends Command
{

    /**
     * constants for the the command
     */
    const COMMAND_NAME = 'catalog:product:urls:rebuild';
    const COMMAND_DESCRIPTION = 'Rebuild the URLs Rewrite for products';
    const COMMAND_HELP_TEXT = 'This commands help to rewrite the products URL.';

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @var CollectionFactory $productCollection
     */
    private $productCollection;

    /**
     * @var UrlPersistInterface $urlPersist
     */
    private $urlPersist;

    /**
     * @var ProductUrlRewriteGeneratorFactory $productUrlRewriteGeneratorFactory
     */
    private $productUrlRewriteGeneratorFactory;

    /**
     * ProductRebuildUrlRewrite constructor.
     * @param ProductUrlRewriteGeneratorFactory $productUrlRewriteGeneratorFactory
     * @param UrlPersistInterface $urlPersist
     * @param CollectionFactory $productCollection
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param string|null $name
     */
    public function __construct(
        ProductUrlRewriteGeneratorFactory $productUrlRewriteGeneratorFactory,
        UrlPersistInterface $urlPersist,
        CollectionFactory $productCollection,
        StoreManagerInterface $storeManager,
        string $name = null
    ) {
        $this->productUrlRewriteGeneratorFactory = $productUrlRewriteGeneratorFactory;
        $this->urlPersist = $urlPersist;
        $this->productCollection = $productCollection;
        $this->storeManager = $storeManager;
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->setHelp(self::COMMAND_HELP_TEXT);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws UrlAlreadyExistsException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $output->writeln('<info>Starting generates the product URLs for the store ID:' . $store->getId() . '</info>');
            // Get all the products for generating URLs, only the products visible
            $products = $this->productCollection->create()
                ->setStoreId(
                    $store->getId()
                )->addAttributeToSelect(
                    '*'
                )->addAttributeToFilter(
                    'visibility',
                    ['neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE]
                );

            foreach ($products as $product) {
                $output->writeln('<info>The product URL Key : ' . $product->getProductUrl() . '</info>');
                // Get the product category ids for generating product urls has the url key of category in the path.
                $productCategoryIds = $product->getCategoryIds();

                foreach ($productCategoryIds as $categoryId) {
                    $this->urlPersist->replace($this->productUrlRewriteGeneratorFactory->create()->generate($product, $categoryId));
                }
            }
            $output->writeln('<info>The End.</info>');
        }
        $output->writeln('<info>Rebuilding the URLs for products successfully.</info>');
    }
}
