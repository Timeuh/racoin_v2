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
        $errors                          = array();
        $errors['nameAdvertiser']        = '';
        $errors['emailAdvertiser']       = '';
        $errors['phoneAdvertiser']       = '';
        $errors['villeAdvertiser']       = '';
        $errors['departmentAdvertiser']  = '';
        $errors['categorieAdvertiser']   = '';
        $errors['titleAdvertiser']       = '';
        $errors['descriptionAdvertiser'] = '';
        $errors['priceAdvertiser']       = '';
        $errors['passwordAdvertiser']    = '';

        // On teste que les champs ne soient pas vides et soient de bons types
        if (empty($nom)) {
            $errors['nameAdvertiser'] = 'Veuillez entrer votre nom';
        }
        if (!EmailChecker::isEmail($email)) {
            $errors['emailAdvertiser'] = 'Veuillez entrer une adresse mail correcte';
        }
        if (empty($phone) && !is_numeric($phone)) {
            $errors['phoneAdvertiser'] = 'Veuillez entrer votre numéro de téléphone';
        }
        if (empty($ville)) {
            $errors['villeAdvertiser'] = 'Veuillez entrer votre ville';
        }
        if (!is_numeric($departement)) {
            $errors['departmentAdvertiser'] = 'Veuillez choisir un département';
        }
        if (!is_numeric($categorie)) {
            $errors['categorieAdvertiser'] = 'Veuillez choisir une catégorie';
        }
        if (empty($title)) {
            $errors['titleAdvertiser'] = 'Veuillez entrer un titre';
        }
        if (empty($description)) {
            $errors['descriptionAdvertiser'] = 'Veuillez entrer une description';
        }
        if (empty($price) || !is_numeric($price)) {
            $errors['priceAdvertiser'] = 'Veuillez entrer un prix';
        }
        if (empty($password) || empty($password_confirm) || $password != $password_confirm) {
            $errors['passwordAdvertiser'] = 'Les mots de passes ne sont pas identiques';
        }

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
