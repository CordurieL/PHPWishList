<?php
declare(strict_types=1);

namespace mywishlist\vue;

define('SCRIPT_ROOT', 'http://localhost/FichiersPHP/PHPWishList/PHP/');

use \Slim\Container;

class VueCreation
{
    public array $tab;
    public Container $container;

    public function __construct(array $tab, Container $container)
    {
        $this->tab = $tab;
        $this->container = $container;
    }

    /* methode pour creer un formulaire de creation de liste */
    public function CreationformulaireListe(): string
    {
        $tommorow = (new \DateTime('tomorrow'))->format('Y-m-d');
        $content = "<form method='POST' action=''>
            <p>Créer votre liste de souhait : </p>
            <hr>
            <p>Titre de la liste : </p>
	        <span id='inputInscription'><input size='12' type='text' name ='titre' placeholder='titre' required/></span><br>
	        <p>Description de la liste : </p>
	        <span id='inputInscription'><input size='20' type='text' name ='description' placeholder='descri'/></span><br>
	        <div><p>Date d'expiration de la liste : </p><input type='date' name ='expiration' placeholder='expiration' value='$tommorow' min='$tommorow'/></div><br>
	        <button class='inscri' type='submit'>Créer la liste</button>
            </form>";
        echo "\n";
        return $content;
    }

    /* methode pour creer un formulaire de creation de compte */
    public function creationFormulaireInscription() : string
    {
        $content = "
            <form method='POST' action=''>
            <h2>Inscription :</h2>
	        <input class='fieldinscri' type='text' name ='pseudo' placeholder='pseudo' required/><br>
	        <input class='fieldinscri' type='password' name ='pass' placeholder='mot de passe' required/><br>
	        <input class='fieldinscri' type='password' name ='confirm_pass' placeholder='confirmez le mot de passe' required/><br>
	        <button class='inscri' type='submit'>Inscription</button>
            </form>";
        echo "\n";
        return $content;
    }
    
    /* methode pour creer un formulaire d'authentification' */
    public function creationFormulaireAuthentification() : string
    {
        $content = "<form method='POST' action=''>
               <h2>Authentification :</h2>
	        <input class='fieldinscri' type='text' name ='pseudo' placeholder='pseudo' required/><br>
	        <input class='fieldinscri' type='password' name ='pass' placeholder='mot de passe' required/><br>
	        <button class='inscri' type='submit'>s'authentifier</button>
            </form>";
        echo "\n";
        return $content;
    }

    /* affichage après creation de compte */
    public function compteCree():string
    {
        return "Votre compte a bien été créé ";
    }

    /* affichage après authentification */
    public function authentifie():string
    {
        return "connecte au compte ".$_SESSION['pseudo'];
    }

    /* affichage après creation d'une liste' */
    public function listeCree():string
    {
        $l = $this->tab[0];
        $tokenEdition = "$l[token_edition]";
        $dateDExp = (new \DateTime("$l[expiration]"))->format('d-m-Y');
        $content = "La liste a été créé : <article><h1>$l[titre]</h1>  <br>$l[description] <br>Expire le $dateDExp</article>\n";
        setcookie(
            "TokenEdition:".$tokenEdition,
            $tokenEdition,
            time() + (100 * 365 * 24 * 60 * 60),   //expire dans 100 ans
            "/"
        );
        $url_nouvListe = $this->container->router->pathFor('affUneListe', ['token'=>"$l[token]"]);
        $content.= "<br>votre token d'édition = $tokenEdition a été créé<br><a href=$url_nouvListe>Vous rendre à votre nouvelle liste</a>";
        return $content;
    }

    /* methode pour gerer les erreurs lors d'une inscription*/
    public function erreurins(string $s) : string
    {
        $url_inscription = $this->container->router->pathFor('inscription');
        $content = $s. " ";
        $content .= "<a href=$url_inscription>retour page inscription</a>";
        return "<section>$content</section>";
    }

    /* methode pour gerer les erreurs lors d'une authentification*/
    public function erreurauth() : string
    {
        $url_authentification = $this->container->router->pathFor('authentification');
        $content = "Pseudo ou mot de passe invalide. ";
        $content .= "<a href=$url_authentification>retour page authentification</a>";
        return "<section>$content</section>";
    }

    public function render($selecteur)
    {
        switch ($selecteur) {
            case 1:
            {
                $content = $this->CreationformulaireListe();
                break;
            }
            case 2:
            {
               $content = $this->listeCree();
                break;
            }
            case 8:
            {
                $content = $this->creationFormulaireInscription();
                break;
            }
            case 9:
            {
                $content = $this->compteCree();
                break;
            }
            case 10:
            {
                $content = $this->creationFormulaireAuthentification();
                break;
            }
            case 11:
            {
                $content = $this->authentifie();
                break;
            }
            case 12:
            {
                $content = $this->erreurins("Pseudo déjà utilisé, veuilez réessayer.");
                break;
            }
            case 13:
            {
                $content = $this->erreurins("Confirmation de mot de passe éronné");
                break;
            }
            case 14:
            {
                $content = $this->erreurauth();
                break;
            }
            
        }

        $url_Accueil = $this->container->router->pathFor('Accueil');
        $url_listes = $this->container->router->pathFor('listeDesListes');
        $url_affichageForm = $this->container->router->pathFor('affForm');
        $url_inscription = $this->container->router->pathFor('inscription');
        $url_authentification = $this->container->router->pathFor('authentification');
        $root = SCRIPT_ROOT;


        $html = <<<END
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <title>My WishList</title>
        <meta charset="utf-8"/>
        <link href="$root../Ressources/css/style.css" type="text/css" rel="stylesheet"/>
    </head>
    <body>
        <div id='mainDiv'>
            <div class='upperScreen'>
                <p><h1>My WishList</h1></p>
            </div>
            <nav id='NavigationPrincipale' class='leftScreen'>
                <button class='navigation' onclick="window.location.href='$url_Accueil'">Accueil</button>
                <button class='navigation' onclick="window.location.href='$url_affichageForm'">Créer une nouvelle liste</button>
                <button class='navigation' onclick="window.location.href='$url_listes'">Listes publiques</button>
                <button class='navigation' onclick="window.location.href='$url_inscription'">Inscription</button>
                <button class='navigation' onclick="window.location.href='$url_authentification'">Authentification</button>
            </nav>
            <div class="content">
                $content
            </div>
        </div>
    </body>
</html>
END;
        return $html;
    }
}
