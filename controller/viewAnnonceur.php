<?php
/**
 * Created by PhpStorm.
 * User: ponicorn
 * Date: 26/01/15
 * Time: 00:25
 */

namespace controller;
use model\Annonce;
use model\Annonceur;
use model\Photo;

class viewAnnonceur {
    public function __construct(){
    }
    function afficherAnnonceur($twig, $menu, $chemin, $idAnnonceur, $cat) {
        $this->annonceur = annonceur::find($idAnnonceur);
        if(!isset($this->annonceur)){
            echo "404";
            return;
        }
        $annonces = annonce::where('id_annonceur', '=', $idAnnonceur)->get();

        foreach ($annonces as $annonceActuelle) {
            $annonceActuelle->nb_photo = $annonceActuelle->photo()->count();
            $annonceActuelle->url_photo = $annonceActuelle->nb_photo > 0
                ? $annonceActuelle->photo()->first()->url_photo
                : $chemin . '/img/noimg.png';
        }

        $template = $twig->load("annonceur.html.twig");
        echo $template->render(array('nom' => $this->annonceur,
            "chemin" => $chemin,
            "annonces" => $annonces,
            "categories" => $cat));
    }
}
