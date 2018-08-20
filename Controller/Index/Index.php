<?php

namespace Inchoo\ReindexRequired\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    private $command;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Inchoo\ReindexRequired\Console\Command\IndexerReindexRequiredCommand $command
    )
    {
        $this->command = $command;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->command->execute();
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('/');
    }
}
