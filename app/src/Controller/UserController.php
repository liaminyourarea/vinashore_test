<?php

namespace App\Controller;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;

class UserController extends AbstractController
{
    protected $connection;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->connection = $this->getConnection();
        self::createDbIfNotExist();
    }

    #[Route('/user', methods: ['GET'])]
    public function index(Request $request)
    {
        $action = $request->get("action");
        if ($action == "delete") {
            $id = $request->get("id");
            $user = $this->entityManager->getReference(User::class, $id);
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        return self::renderTemplate();
    }

    #[Route('/user', methods: ['POST'])]
    public function create(Request $request)
    {
        $firstname = $request->get("firstname");
        $lastname = $request->get("lastname");
        $address = $request->get("address");
        $item = new User();
        $item->setData($firstname . " - " . $lastname . " - " . $address);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return self::renderTemplate();
    }

    private function renderTemplate()
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        return $this->render('user.html.twig', [
            'users' => $users
        ]);
    }

    private function createDbIfNotExist()
    {
        $tableExists = $this->executeRequest("SELECT * FROM information_schema.tables WHERE table_schema = 'symfony' AND table_name = 'user' LIMIT 1;");
        if (empty($tableExists)) {
            $this->executeRequest("CREATE TABLE user (id int NOT NULL AUTO_INCREMENT, data varchar(255), PRIMARY KEY (id))");
            $this->executeRequest("INSERT INTO user(id, data) values(1, 'Barack - Obama - White House')");
            $this->executeRequest("INSERT INTO user(id, data) values(2, 'Britney - Spears - America')");
            $this->executeRequest("INSERT INTO user(id, data) values(3, 'Leonardo - DiCaprio - Titanic')");
        }
    }

    private function getConnection()
    {
        $connectionParams = [
            'dbname' => 'symfony',
            'user' => 'symfony',
            'password' => '',
            'host' => 'mariadb',
            'driver' => 'pdo_mysql',
        ];

        return DriverManager::getConnection($connectionParams);
    }

    private function executeRequest($sql)
    {
        $stmt = $this->connection->prepare($sql);

        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
