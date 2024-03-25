<?php
require 'vendor/autoload.php';

use controller\getCategorie;
use controller\getDepartment;
use controller\index;
use controller\item;
use db\connection;

use model\Annonce;
use model\Categorie;
use model\Annonceur;
use model\Departement;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


connection::createConnection();

// Initialisation de Slim
$app = new App([
    'settings' => [
        'displayErrorDetails' => true,
    ],
]);

// Initialisation de Twig
$loader = new FilesystemLoader(__DIR__ . '/template');
$twig   = new Environment($loader);

// Ajout d'un middleware pour le trailing slash
$app->add(function (Request $request, Response $response, $next) {
    $uri  = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && str_ends_with($path, '/')) {
        $uri = $uri->withPath(substr($path, 0, -1));
        if ($request->getMethod() == 'GET') {
            return $response->withRedirect((string)$uri, 301);
        } else {
            return $next($request->withUri($uri), $response);
        }
    }
    return $next($request, $response);
});


if (!isset($_SESSION)) {
    session_start();
    $_SESSION['formStarted'] = true;
}

if (!isset($_SESSION['token'])) {
    $token                  = md5(uniqid(rand(), TRUE));
    $_SESSION['token']      = $token;
    $_SESSION['token_time'] = time();
} else {
    $token = $_SESSION['token'];
}

$menu = [
    [
        'href' => './index.php',
        'text' => 'Accueil'
    ]
];

$chemin = dirname($_SERVER['SCRIPT_NAME']);

$categorie = new getCategorie();
$dpt = new getDepartment();

$app->get('/', function () use ($twig, $menu, $chemin, $categorie) {
    $index = new index();
    $index->displayAllAnnonce($twig, $menu, $chemin, $categorie->getCategories());
});

$app->get('/item/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $categorie) {
    $itemId     = $arg['n'];
    $item = new item();
    $item->afficherItem($twig, $menu, $chemin, $itemId, $categorie->getCategories());
});

$app->get('/add', function () use ($twig, $app, $menu, $chemin, $categorie, $dpt) {
    $controllerAjoutItem = new controller\addItem();
    $controllerAjoutItem->addItemView($twig, $menu, $chemin, $categorie->getCategories(), $dpt->getAllDepartments());
});

$app->post('/add', function ($request) use ($twig, $app, $menu, $chemin) {
    $allPostVars = $request->getParsedBody();
    $controllerAjoutItem       = new controller\addItem();
    $controllerAjoutItem->addNewItem($twig, $menu, $chemin, $allPostVars);
});

$app->get('/item/{id}/edit', function ($request, $response, $arg) use ($twig, $menu, $chemin) {
    $id   = $arg['id'];
    $item = new item();
    $item->modifyGet($twig, $menu, $chemin, $id);
});
$app->post('/item/{id}/edit', function ($request, $response, $arg) use ($twig, $app, $menu, $chemin, $categorie, $dpt) {
    $id          = $arg['id'];
    $allPostVars = $request->getParsedBody();
    $item        = new item();
    $item->modifyPost($twig, $menu, $chemin, $id, $allPostVars, $categorie->getCategories(), $dpt->getAllDepartments());
});

$app->map(['GET, POST'], '/item/{id}/confirm', function ($request, $response, $arg) use ($twig, $app, $menu, $chemin) {
    $id   = $arg['id'];
    $allPostVars = $request->getParsedBody();
    $item        = new item();
    $item->edit($twig, $menu, $chemin, $id, $allPostVars);
});

$app->get('/search', function () use ($twig, $menu, $chemin, $categorie) {
    $searchController = new controller\Search();
    $searchController->show($twig, $menu, $chemin, $categorie->getCategories());
});


$app->post('/search', function ($request, $response) use ($app, $twig, $menu, $chemin, $categorie) {
    $array = $request->getParsedBody();
    $searchController     = new controller\Search();
    $searchController->research($array, $twig, $menu, $chemin, $categorie->getCategories());

});

$app->get('/annonceur/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $categorie) {
    $annonceurId         = $arg['n'];
    $annonceur = new controller\viewAnnonceur();
    $annonceur->afficherAnnonceur($twig, $menu, $chemin, $annonceurId, $categorie->getCategories());
});

$app->get('/del/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin) {
    $categorieId    = $arg['n'];
    $item = new controller\item();
    $item->supprimerItemGet($twig, $menu, $chemin, $categorieId);
});

$app->post('/del/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $categorie) {
    $categorieId    = $arg['n'];
    $item = new controller\item();
    $item->supprimerItemPost($twig, $menu, $chemin, $categorieId, $categorie->getCategories());
});

$app->get('/cat/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $categorie) {
    $categorieId = $arg['n'];
    $categorie = new controller\getCategorie();
    $categorie->displayCategorie($twig, $menu, $chemin, $categorie->getCategories(), $categorieId);
});

$app->get('/api(/)', function () use ($twig, $menu, $chemin, $categorie) {
    $template = $twig->load('api.html.twig');
    $menu     = array(
        array(
            'href' => $chemin,
            'text' => 'Acceuil'
        ),
        array(
            'href' => $chemin . '/api',
            'text' => 'Api'
        )
    );
    echo $template->render(array('breadcrumb' => $menu, 'chemin' => $chemin));
});

$app->group('/api', function () use ($app, $twig, $menu, $chemin, $categorie) {

    $app->group('/annonce', function () use ($app) {

        $app->get('/{id}', function ($request, $response, $arg) use ($app) {
            $id          = $arg['id'];
            $annonceList = ['id_annonce', 'id_categorie as categorie', 'id_annonceur as annonceur', 'id_departement as departement', 'prix', 'date', 'titre', 'description', 'ville'];
            $return      = Annonce::select($annonceList)->find($id);

            if (isset($return)) {
                $response->headers->set('Content-Type', 'application/json');
                $return->categorie     = Categorie::find($return->categorie);
                $return->annonceur     = Annonceur::select('email', 'nom_annonceur', 'telephone')
                    ->find($return->annonceur);
                $return->departement   = Departement::select('id_departement', 'nom_departement')->find($return->departement);
                $links                 = [];
                $links['self']['href'] = '/api/annonce/' . $return->id_annonce;
                $return->links         = $links;
                echo $return->toJson();
            } else {
                $app->notFound();
            }
        });
    });

    $app->group('/annonces(/)', function () use ($app) {

        $app->get('/', function ($request, $response) use ($app) {
            $annonceList = ['id_annonce', 'prix', 'titre', 'ville'];
            $response->headers->set('Content-Type', 'application/json');
            $annonces     = Annonce::all($annonceList);
            $links = [];
            foreach ($annonces as $annonce) {
                $links['self']['href'] = '/api/annonce/' . $annonce->id_annonce;
                $annonce->links            = $links;
            }
            $links['self']['href'] = '/api/annonces/';
            $annonces->links              = $links;
            echo $annonces->toJson();
        });
    });


    $app->group('/categorie', function () use ($app) {

        $app->get('/{id}', function ($request, $response, $arg) use ($app) {
            $id = $arg['id'];
            $response->headers->set('Content-Type', 'application/json');
            $annonceCateg     = Annonce::select('id_annonce', 'prix', 'titre', 'ville')
                ->where('id_categorie', '=', $id)
                ->get();
            $links = [];

            foreach ($annonceCateg as $annonceActuelle) {
                $links['self']['href'] = '/api/annonce/' . $annonceActuelle->id_annonce;
                $annonceActuelle->links            = $links;
            }

            $categories                     = Categorie::find($id);
            $links['self']['href'] = '/api/categorie/' . $id;
            $categories->links              = $links;
            $categories->annonces           = $annonceCateg;
            echo $categories->toJson();
        });
    });

    $app->group('/categories(/)', function () use ($app) {
        $app->get('/', function ($request, $response, $arg) use ($app) {
            $response->headers->set('Content-Type', 'application/json');
            $categories     = Categorie::get();
            $links = [];
            foreach ($categories as $category) {
                $links['self']['href'] = '/api/categorie/' . $category->id_categorie;
                $category->links            = $links;
            }
            $links['self']['href'] = '/api/categories/';
            $categories->links              = $links;
            echo $categories->toJson();
        });
    });

    $app->get('/key', function () use ($app, $twig, $menu, $chemin, $categorie) {
        $keyGenerator = new controller\KeyGenerator();
        $keyGenerator->show($twig, $menu, $chemin, $categorie->getCategories());
    });

    $app->post('/key', function () use ($app, $twig, $menu, $chemin, $categorie) {
        $nom = $_POST['nom'];

        $keyGenerator = new controller\KeyGenerator();
        $keyGenerator->generateKey($twig, $menu, $chemin, $categorie->getCategories(), $nom);
    });
});


$app->run();
