<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Twig;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MonthlyBillingReport extends Command
{
    protected static $defaultName = 'payment:report';

    private $twig;
    private $transactionRepository;
    private $mailer;

    public function __construct(
        TransactionRepository $transactionRepository,
        Twig                  $twig,
        MailerInterface       $mailer
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->twig = $twig;
        $this->mailer = $mailer;

        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $transactions = $this->transactionRepository->findAll();

        $untilWhat = new \DateTime("today", new \DateTimeZone('UTC'));

        $sinceWhen = new \DateTime("today", new \DateTimeZone('UTC'));
        $sinceWhen->modify('-1 month');

        $dataTransaction = [];

        foreach ($transactions as $transaction) {
            if ($transaction->getCourse() != null) {
                $dateCreate = $transaction->getDateAndTime();
                if ((strtotime($sinceWhen->format('Y-m-d H:i:sP')) < strtotime($dateCreate->format('Y-m-d H:i:sP')))
                    &&
                    (strtotime($dateCreate->format('Y-m-d H:i:sP')) < strtotime($untilWhat->format('Y-m-d H:i:sP')))
                ) {
                    $dataTransaction[] = $transaction;
                }
            }
        }

        $itog = 0;

        $arrayToOutput = [];

        foreach ($dataTransaction as $transaction) {
            $key = $transaction->getCourse()->getTitle();
            if (array_key_exists($key, $arrayToOutput)) {
                $arrayToOutput[$key]['amount']++;
                $arrayToOutput[$key]['sum'] += $transaction->getValue();
                $itog += $transaction->getValue();
            } else {
                $arrayToOutput[$key]['name'] = $key;
                if ($transaction->getCourse()->getType() == 'rent') {
                    $arrayToOutput[$key]['type'] = 'Аренда';
                } else {
                    $arrayToOutput[$key]['type'] = 'Полный';
                }
                $arrayToOutput[$key]['amount'] = 1;
                $arrayToOutput[$key]['sum'] = $transaction->getValue();
                $itog += $transaction->getValue();
            }
        }

        $html = $this->twig->render(
            'billing_report.html.twig',
            [
                'data' => $arrayToOutput,
                'itog' => $itog,
                'dateOutput' => 'Отчет об оплаченных курсах за период ' .
                    $sinceWhen->format('d.m.Y') .
                    ' - ' .
                    $untilWhat->format('d.m.Y')
            ]
        );

        $email = (new Email())
            ->from('hello@example.com')
            ->to($_ENV["ADMIN_EMAIL"])
            ->html($html);

        $this->mailer->send($email);

        return Command::SUCCESS;
    }
}
