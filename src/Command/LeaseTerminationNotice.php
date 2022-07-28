<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Service\Twig;

class LeaseTerminationNotice extends Command
{
    protected static $defaultName = 'payment:ending:notification';

    private $userRepository;
    private $transactionRepository;
    private $mailer;
    private $twig;

    public function __construct(
        UserRepository        $userRepository,
        TransactionRepository $transactionRepository,
        MailerInterface       $mailer,
        Twig                  $twig
    ) {
        $this->userRepository = $userRepository;
        $this->transactionRepository = $transactionRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;

        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $users = $this->userRepository->findAll();

        $untilWhat = new \DateTime("today", new \DateTimeZone('UTC'));
        $untilWhat->modify('+3 day');
        $untilWhat = strtotime($untilWhat->format('Y-m-d H:i:sP'));

        $sinceWhen = $untilWhat - 24 * 60 * 60;

        foreach ($users as $user) {
            $workTransaction = [];
            $transactions = $this->transactionRepository->findBy(['Client' => $user]);
            foreach ($transactions as $transaction) {
                if ($transaction->getCourse() != null) {
                    if ($transaction->getCourse()->getType() == 'rent') {
                        $endTime = strtotime($transaction->getValidUntil()->format('Y-m-d H:i:sP'));
                        if (($sinceWhen < $endTime) && ($endTime < $untilWhat)) {
                            $workTransaction[] = $transaction;
                        }
                    }
                }
            }

            if ($workTransaction != []) {
                $data = [];

                $count = 0;
                foreach ($workTransaction as $transaction) {
                    $data[$count]['title'] = $transaction->getCourse()->getTitle();
                    $data[$count]['date'] = $transaction->getValidUntil()->format('d.m.Y H:i');
                    $count++;
                }

                $html = $this->twig->render(
                    'lease_termination.html.twig',
                    [
                        'data' => $data,
                    ]
                );

                $email = (new Email())
                    ->from('studyOn@company.com')
                    ->to($user->getEmail())
                    ->html($html);

                $this->mailer->send($email);
            }
        }

        return Command::SUCCESS;
    }
}
