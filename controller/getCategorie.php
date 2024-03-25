<?php

namespace controller;

use model\Annonce;
use model\Categorie;

class getCategorie
{
    protected $categories = array();

    public function getCategories()
    {
        return Categorie::orderBy('nom_categorie')->get()->toArray();
    }

    public function getCategorieContent($chemin, $categorieId): array
    {
        $annonces = Annonce::with("Annonceur")->orderBy('id_annonce', 'desc')->where('id_categorie', "=", $categorieId)->get();
        $annoncesToReturn = [];
        foreach($annonces as $annonce) {
            $annonce->nb_photo = $annonce->photo->count();
            $annonce->url_photo =  $annonce->photo->first()->url_photo ?? $chemin.'/img/noimg.png';
            $annonce->nom_annonceur = $annonce->annonceur->nom_annonceur ?? "Anonyme";
            $annoncesToReturn[] = $annonce;
        }
        return $annoncesToReturn;
    }

    public function displayCategorie($twig, $chemin, $categories, $categorieId): void
    {
        $template = $twig->load("index.html.twig");
        $menu = [
            [
                'href' => $chemin,
                'text' => 'Acceuil'
            ],
            [
                'href' => $chemin."/cat/".$categorieId,
                'text' => Categorie::find($categorieId)->nom_categorie
            ]
        ];

        $annonces = $this->getCategorieContent($chemin, $categorieId);
        echo $template->render(array(
            "breadcrumb" => $menu,
            "chemin" => $chemin,
            "categories" => $categories,
            "annonces" => $annonces));
    }
}
