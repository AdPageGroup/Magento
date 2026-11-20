<?php declare(strict_types=1);

namespace Tagging\GTM\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tagging\GTM\Model\Botfilter\Sync;

/** bin/magento tagging:botfilter:sync — fetch the storefront rules now and show what came back. */
class BotfilterSyncCommand extends Command
{
    private Sync $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('tagging:botfilter:sync');
        $this->setDescription('Fetch the storefront rules of the AdPage Botfilter now');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->sync->run();
        if ($report === []) {
            $output->writeln('No store view has a Botfilter key (Stores > Configuration > AdPage > AdPage GTM > Botfilter).');
            return 0;
        }

        $failed = false;
        foreach ($report as $row) {
            $stores = $row['stores'] !== [] ? implode(', ', $row['stores']) : 'unknown domain';
            if (!$row['fetched']) {
                $failed = true;
                $output->writeln(sprintf(
                    '<error>%s (%s): could not fetch the rules, see var/log/Tagging_GTM.log. In use: %d User-Agents, %d pages.</error>',
                    $row['container'],
                    $stores,
                    $row['userAgents'],
                    $row['urls']
                ));
                continue;
            }
            $output->writeln(sprintf(
                '%s (%s): %d User-Agents, %d pages%s',
                $row['container'],
                $stores,
                $row['userAgents'],
                $row['urls'],
                $row['changed'] ? ' (changed, full page cache cleaned)' : ''
            ));
        }

        return $failed ? 1 : 0;
    }
}
