<?php

namespace CompanyDataProvider\Command\Cleanup;

use CompanyDataProvider\Service\DataBus\WebsiteContentDataBus;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TypeError;

class CleanWebsiteCache extends Command
{
    private const COMMAND_NAME = "company-data-provider:cache:clean:websites";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface       $logger
     * @param WebsiteContentDataBus $websiteContentDataBus
     */
    public function __construct(
        private readonly LoggerInterface       $logger,
        private readonly WebsiteContentDataBus $websiteContentDataBus
    ) {
        parent::__construct(self::COMMAND_NAME);
    }

    protected function configure(): void
    {
        $this->setDescription("Will remove the websites expired cache");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        try {
            $io->info("Started removing website cache");
            $this->websiteContentDataBus->removeExpired();
            $io->info("Finished removing website cache");
        } catch (Exception|TypeError $e) {
            $this->logger->critical("Exception was thrown while calling command", [
                "class"     => self::class,
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ],
            ]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}
