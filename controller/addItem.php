<?php

namespace controller;

use common\functions\EmailChecker;
use model\Annonce;
use model\Annonceur;

class addItem
{
    public function addItemView($twig, $menu, $chemin, $cat, $dpt)
    {
        $template = $twig->load("add.html.twig");
        echo $template->render(
            array(
                "breadcrumb"   => $menu,
                "chemin"       => $chemin,
                "categories"   => $cat,
                "departements" => $dpt
            )
        );
    }

    public function addNewItem($twig, $menu, $chemin, $allPostVars)
    {
        date_default_timezone_set('Europe/Paris');

        /*
        * On récupère tous les champs du formulaire en supprimant
        * les caractères invisibles en début et fin de chaîne.
        */
        $nom              = trim($_POST['nom']);
        $email            = trim($_POST['email']);
        $phone            = trim($_POST['phone']);
        $ville            = trim($_POST['ville']);
        $departement      = trim($_POST['departement']);
        $categorie        = trim($_POST['categorie']);
        $title            = trim($_POST['title']);
        $description      = trim($_POST['description']);
        $price            = trim($_POST['price']);
        $password         = trim($_POST['psw']);
        $password_confirm = trim($_POST['confirm-psw']);

        // Tableau d'erreurs personnalisées
        $errors = [];
        // On teste que les champs ne soient pas vides et soient de bons types
        $errors['nameAdvertiser'] = empty($nom) ? 'Veuillez entrer votre nom' : '';
        $errors['emailAdvertiser'] = !EmailChecker::isEmail($email) ? 'Veuillez entrer une adresse mail correcte' : '';
        $errors['phoneAdvertiser'] = empty($phone) && !is_numeric($phone) ? 'Veuillez entrer votre numéro de téléphone' : '';
        $errors['villeAdvertiser'] = empty($ville) ? 'Veuillez entrer votre ville' : '';
        $errors['departmentAdvertiser'] = !is_numeric($departement) ? 'Veuillez choisir un département' : '';
        $errors['categorieAdvertiser'] = !is_numeric($categorie) ? 'Veuillez choisir une catégorie' : '';
        $errors['titleAdvertiser'] = empty($title) ? 'Veuillez entrer un titre' : '';
        $errors['descriptionAdvertiser'] = empty($description) ? 'Veuillez entrer une description' : '';
        $errors['priceAdvertiser'] = empty($price) || !is_numeric($price) ? 'Veuillez entrer un prix' : '';
        $errors['passwordAdvertiser'] = empty($password) || empty($password_confirm) || $password != $password_confirm ? 'Les mots de passes ne sont pas identiques' : '';

        // On vire les cases vides
        $errors = array_values(array_filter($errors));

        // S'il y a des erreurs on redirige vers la page d'erreur
        if (!empty($errors)) {

            $template = $twig->load("add-error.html.twig");
            echo $template->render(
                array(
                    "breadcrumb" => $menu,
                    "chemin"     => $chemin,
                    "errors"     => $errors
                )
            );
        } // sinon on ajoute à la base et on redirige vers une page de succès
        else {
            $annonce   = new Annonce();
            $annonceur = new Annonceur();

            $annonceur->email         = htmlentities($allPostVars['email']);
            $annonceur->nom_annonceur = htmlentities($allPostVars['nom']);
            $annonceur->telephone     = htmlentities($allPostVars['phone']);

            $annonce->ville          = htmlentities($allPostVars['ville']);
            $annonce->id_departement = $allPostVars['departement'];
            $annonce->prix           = htmlentities($allPostVars['price']);
            $annonce->mdp            = password_hash($allPostVars['psw'], PASSWORD_DEFAULT);
            $annonce->titre          = htmlentities($allPostVars['title']);
            $annonce->description    = htmlentities($allPostVars['description']);
            $annonce->id_categorie   = $allPostVars['categorie'];
            $annonce->date           = date('Y-m-d');


            $annonceur->save();
            $annonceur->annonce()->save($annonce);


            $template = $twig->load("add-confirm.html.twig");
            echo $template->render(array("breadcrumb" => $menu, "chemin" => $chemin));
        }
    }
}
