<?php

namespace Inchoo\ReindexRequired\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\Config\DependencyInfoProvider;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Indexer\StateInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Indexer\ConfigInterface;
use Magento\Framework\App\ObjectManagerFactory;

class IndexerReindexRequiredCommand extends \Magento\Indexer\Console\Command\AbstractIndexerManageCommand
{
    /**
     * @var array
     */
    private $indexerRegistry;
    private $dependencyInfoProvider;
    private $config;

    public function __construct(
        ObjectManagerFactory $objectManagerFactory,
        IndexerRegistry $indexerRegistry = null,
        DependencyInfoProvider $dependencyInfoProvider = null,
        ConfigInterface $config,
        \Magento\Indexer\Model\Indexer\CollectionFactory $collectionFactory
    )
    {
        $this->indexerRegistry = $indexerRegistry;
        $this->dependencyInfoProvider = $dependencyInfoProvider;
        $this->config = $config;
        parent::__construct($objectManagerFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('indexer:reindex:required')
            ->setDescription('Reindexes Required Data')
            ->setDefinition($this->getInputList());

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $returnValue = Cli::RETURN_FAILURE;
        foreach ($this->getAllIndexers() as $indexer) {
            try {
                $this->validateIndexerStatus($indexer);

                $startTime = microtime(true);
                $indexer->reindexAll();
                $resultTime = microtime(true) - $startTime;
                $output->writeln(
                    $indexer->getTitle() . ' index has been rebuilt successfully in ' . gmdate('H:i:s', $resultTime)
                );

                $returnValue = Cli::RETURN_SUCCESS;
            } catch (LocalizedException $e) {
                $output->writeln($e->getMessage());
            } catch (\Exception $e) {
                $output->writeln($indexer->getTitle() . ' indexer process unknown error:');
                $output->writeln($e->getMessage());
            }
        }
        return $returnValue;
    }

    /**
     * Validate that indexer is not locked or not needed to be reindexed
     *
     * @param IndexerInterface $indexer
     * @return void
     * @throws LocalizedException
     */
    private function validateIndexerStatus(IndexerInterface $indexer)
    {
        if ($indexer->getStatus() == StateInterface::STATUS_WORKING) {
            throw new LocalizedException(
                __(
                    '%1 index is locked by another reindex process. Skipping.',
                    $indexer->getTitle()
                )
            );
        } elseif ($indexer->getStatus() == StateInterface::STATUS_VALID) {
            throw new LocalizedException(
                __(
                    '%1 index is valid. Skipping.',
                    $indexer->getTitle()
                )
            );
        }
    }
}
