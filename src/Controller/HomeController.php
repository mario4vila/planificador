<?php

namespace App\Controller;

use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'inicio', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    /**
     *@Route("/home",name="make_table", methods="POST")
     */
    public function table(Request $request)
    {
        $submittedToken = $request->request->get('token');
        if (!$this->isCsrfTokenValid('make-table', $submittedToken)) {
            return new Response('Access Denied');
        }

        $data = [
            'periodos' => $request->get('periodos', null),
            'total'  => $request->get('total', null),
            'interes' => $request->get('interes', null),
        ];

        $validator = Validation::createValidator();
        $errors_total  = $validator->validate($data['total'], [new NotNull()]);
        $errors_periodos = $validator->validate($data['periodos'], [new NotNull()]);
        $errors_interes = $validator->validate($data['interes'], [new NotNull()]);

        if (count($errors_periodos) > 0 || count($errors_total) > 0 || count($errors_interes) > 0) {
            $errors = [
                'errors_periodos' => $errors_periodos,
                'errors_inicial'  => $errors_total,
                'errors_interes' => $errors_interes,
            ];
            $data = array_merge($data, $errors);
            return $this->render('home/index.html.twig', $data);
        }

        // Sacamos el interes anual
        $interes    = ($data['interes'] / 100) / 12;
        $montoAhorro = $data['total'] / (((1 + $interes) ** $data['periodos'] - 1) / $interes);

        //cuota=0 - fecha=1 - capital=2 - interes=3 - ahorro=4
        //$fecha_inicial = date("Y-m-d",strtotime( $fecha_inicial ));
        $table = array();

        $totalAhorrado = 0;
        for ($i = 1; $i <= $data['periodos']; $i++) {

            $detail = new stdClass();
            $detail->cuota = $i;
            $detail->capital = $totalAhorrado;
            $detail->interes = $totalAhorrado * $interes;
            $detail->ahorro = $montoAhorro;
            $detail->total = $detail->capital + $detail->interes + $detail->ahorro;

            $totalAhorrado = $detail->total;
            array_push($table, $detail);
        }

        $data['table'] =  $table;

        return $this->render('home/index.html.twig', $data);
    }

    /**
     * @Route("/email")
     */
    public function sendEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('info@hagamostrato.com')
            ->to('marioo.rab@gmail.com')
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $mailer->send($email);

        return new Response('guay revisa tu email');
    }
}
