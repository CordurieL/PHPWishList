<?php
declare(strict_types=1);

namespace mywishlist\vue;

define('SCRIPT_ROOT', 'http://localhost/FichiersPHP/PHPWishList/PHP/');

use \Slim\Container;

class VueParticipant
{
    public array $tab;
    public Container $container;

    // Constructeur
    public function __construct(array $tab, Container $container)
    {
        $this->tab = $tab;
        $this->container = $container;
    }

    // Affiche l'accueil
    private function htmlAccueil() : string
    {
        // Bienvenue et votre pseudo si vous etes connecte
        $content = "Bienvenue à l'accueil";
        if (isset($_SESSION['pseudo'])) {
            $content = $content . ' ' . $_SESSION['pseudo'];
        }
        // Vos listes crees
        $content .= "<br><h1>Vos créations :</h1>";
        $listesCrea = $this->tab[1];
        usort($listesCrea, function ($l1, $l2) {
            $exp1 = strtotime($l1['expiration']);
            $exp2 = strtotime($l2['expiration']);
            return $exp1 - $exp2;
        });
        foreach ($listesCrea as $l) {
            $etatliste ="";
            if ($l['valide']) {
                $etatliste ="publique";
            } else {
                $etatliste ="privée";
            }
            $url = $this->container->router->pathFor('affUneListe', ['token'=>$l['token']]);
            $content .= "<a href=$url><article class='homeCrea'><h3>$l[titre] : <span>$etatliste</span></h3></article></a>";
        }
        // Les listes que vous avez vu mais pas crees (pas expirees)
        $content .= "<hr><h1>Les listes que vous avez consultées et dont vous n'êtes pas l'auteur :</h1>";
        $listes = $this->tab[0];
        usort($listes, function ($l1, $l2) {
            $exp1 = strtotime($l1['expiration']);
            $exp2 = strtotime($l2['expiration']);
            return $exp1 - $exp2;
        });
        foreach ($listes as $l) {
            $etatliste ="";
            if (!$l['valide']) {
                $etatliste =" : encore inacessible";
            }
            $dateDExp = (new \DateTime("$l[expiration]"));
            if (((new \DateTime()) < $dateDExp) && (!isset($_COOKIE["TokenEdition:".$l['token_edition']]))) {
                $url = $this->container->router->pathFor('affUneListe', ['token'=>$l['token']]);
                $content .= "<a href=$url><article class='homeCrea'><h3>$l[titre]<span>$etatliste</span></h3></article></a>";
            }
        }
        return "<section>$content</section>";
    }

    // Affiche les listes publiques et non expirees
    private function htmlListes() : string
    {
        $content = "<h1>Listes publiques :</h1>";
        $listes = $this->tab;
        usort($listes, function ($l1, $l2) {
            $exp1 = strtotime($l1['expiration']);
            $exp2 = strtotime($l2['expiration']);
            return $exp1 - $exp2;
        });
        foreach ($listes as $l) {
            $dateDExp = (new \DateTime("$l[expiration]"));
            if ($l['valide'] && ((new \DateTime()) < $dateDExp)) {
                $url = $this->container->router->pathFor('affUneListe', ['token'=>$l['token']]);
                $content .= "<a href=$url><article><h3>$l[titre]</h3></article></a>";
            }
        }
        return "<section>$content</section>";
    }

    // Affiche une liste
    private function htmlUneListe() : string
    {
        // Infos generales
        $l = $this->tab[0];
        $dateDExp = (new \DateTime("$l[expiration]"));
        $dateDExpString = $dateDExp->format('d-m-Y');
        $tokenEdition = "$l[token_edition]";

        $content = "";
        // Vous etes le proprietaire
        if (isset($_COOKIE["TokenEdition:".$tokenEdition])) {
            $content .= "
            <script type='text/javascript'>
                function copierLUrl() 
                {
                    ZoneUrl.innerHTML = window.location.href;
                    var copyTextarea = document.getElementById('ZoneUrl');
                    copyTextarea.select();
                    document.execCommand('copy');
                }

                var button = document.getElementById('Bcree');
                function verifChamps()
                {
                    if (document.getElementByid('cnom').value == '') {
                        window.alert('remplissez les champs vides');
                    }
                }

                function verifChamps() {
                    var a = document.forms['FormAjoutItem']['creanom'].value;
                    var b = document.forms['FormAjoutItem']['creadescription'].value;
                    var c = document.forms['FormAjoutItem']['creatarif'].value;
                    if (a == null || a == '', b == null || b == '', c == null || c == '') {
                      alert('remplissez les champs');
                      return false;
                    }
                  }

                var button = document.getElementById('Bcree');
                button.onclick = verifChamps;

            </script>
            <div>";
            if ($l['valide'] == 1) {
                $content .= "<input type='button' value='Copier le lien à cette page' onclick='copierLUrl();' />
                <textarea id='ZoneUrl' rows='1' cols='65'></textarea>";
            } else {
                $content .= "<form method='POST' action=''>
                <button class='bout' name='publicationButton' type='submit'>Rendre la liste publique</button>
                </form>";
            }
            $tommorow = (new \DateTime('tomorrow'))->format('Y-m-d');
            $normalExp = (new \DateTime($l['expiration']))->format('Y-m-d');
            $content .="</div>
            <br>
            <form method='POST' action=''>
            <div>Modifier la liste: </div>
	        <input type='text' name ='editerTitre' placeholder='Titre'/>
	        <input type='text' name ='editerDescr' placeholder='Description'/>
	        <i>Date d'expiration</i> <input type='date' name ='editerDateExp' placeholder='Date expiration'  value='$normalExp' min='$tommorow'/>
            <button type='submit'>Modifier la liste</button>
            </form>
            <br>
            <br>

            <form enctype='multipart/form-data' method='POST' action='' id='FormAjoutItem'>
            <div>Ajouter un item à la liste: </div>
            <input id='cnom' type='text' name='creanom' placeholder='nom' required/>
            <input id='cdesc' type='text' name='creadescription' placeholder='description'/>
            <input id='crtar' type='number'  style='width: 6em' name='creatarif' placeholder='tarif' step='0.01' min='0'  required/>
            <input type='url' name='creaurl' placeholder='url'/>

                <span>| image(.jpg, .png ou .gif):</span>
                    <input type='file' name='creaimage' placeholder='creaimage'></td>
                    <span>ou </span>
                    <input type='url' name='creaurlimage' placeholder='url_image'></td>


            <button type='submit' id='Bcree' onClick='verifChamps();' >Créer l'item</button>
            </form>
            <br>";
        } else {
            // vous n'etes pas le proprietaire
            setcookie(
                "TokenAcces:".$l['token'],
                $l['token'],
                strtotime($dateDExpString) + (24 * 60 * 60),
                "/"
            );
        }
        // vous etes ou n'etes pas le proprietaire
        $content .="<article><h1>Liste de souhaits : $l[titre]</h1><br><b>Description :</b> <i>$l[description]</i> <br>Expire le $dateDExpString<br><small>Liste numéro $l[no] <br>Par l'utilisateur ayant l'id $l[user_id]</small> </article>\n";
        $item = $this->tab[1];
        $url = $this->container->router->pathFor('affUneListe', ['token'=>$l['token']]);
        // On affiche les items de la liste
        $content .= "<ul>";
        foreach ($item as $i) {
            $idItem = $i['id'];
            $url = $this->container->router->pathFor('affUnItem', ['id'=>$i['id'], 'token'=>$l['token']]);
            $content .= "<div><li><a href='$url'>$i[nom]</a> : ";
            if (isset($_COOKIE["TokenEdition:".$tokenEdition]) && ((new \DateTime()) < $dateDExp)) {
                $content .= "C'est vous qui avez créé la liste, vous ne pouvez pas voir qui a réservé cet item avant le $dateDExpString<br>";
            } else {
                if ($i['nomReservation'] == null) {
                    if ($i['estUneCagnotte'] == false) {
                        $content .= "Pas encore réservé<br>";
                    } else {
                        $content .= "Pas encore réservé ou seulement partiellement réservé<br>";
                    }
                } else {
                    if ($i['estUneCagnotte'] == false) {
                        $content .= "Réservé par $i[nomReservation]<br>";
                    } else {
                        $content .= "Réservé par de multiples participants<br>";
                    }
                }
            }
            $content .= "<br><img style='max-width: 200px' src='../../Ressources/img/$i[img]'></div><br>";
        }
        /* Pour les messages dans les listes */
        $content .= "</ul><hr>";
        $content .= "<form method='POST' action=''>
	        <textarea name ='contenu' placeholder='Message' maxlength=255 cols=50 rows=8></textarea><br>
	        <button class='bout' type='submit'>Publier le message</button>
            </form><br>";
        $message = $this->tab[2];
        foreach ($message as $m) {
            $content .= "<div>$m[contenu]</div><br>";
        }
        return "<section>$content</section>";
    }
    
    // Affiche un item
    private function htmlUnItem() : string
    {
        //Recuperation du cookie nom
        $champ = "";
        if (isset($_COOKIE["nomReservation"])) {
            $champ .= $_COOKIE["nomReservation"];
        }

        // Infos generales
        $i = $this->tab[0];
        $l = $this->tab[1];
        $tokenEdition = "$l[token_edition]";
        $dateDExp = (new \DateTime("$l[expiration]"));
        $dateDExpString = $dateDExp->format('d-m-Y');
        $cette_liste = $this->container->router->pathFor('affUneListe', ['token'=>$l['token']]);
        
        // Retour a la liste
        $content = "<button id='boutonRetourListe' class='bout' onclick=\"window.location.href='$cette_liste'\">← Retour à la liste</button><br>";

        if (isset($_COOKIE["TokenEdition:".$tokenEdition])) {
            //Vous etes l'auteur de la liste qui contient l'item
            $content .= "CET ITEM FAIT PARTIE DE VOTRE LISTE DE SOUHAIT N°$l[no] DE TOKEN $l[token] <br>";
            //Transformer en cagnotte
            if ($i['nomReservation'] == null) {
                if ($i['estUneCagnotte'] == false) {
                    $content .= "<form method='POST' action=''>
            <button name='rendreCagnotte' type='submit'>Transformer en cagnotte (⚠️ irréversible ⚠️)</button>
            </form>";
                } else {
                    $content .= "Vous avez créé une cagnotte pour cet objet.";
                }
            }
        } else {
            // vous n'etes pas l'auteur de la liste qui contient l'item
            setcookie(
                "TokenAcces:".$l['token'],
                $l['token'],
                strtotime($dateDExpString) + (24 * 60 * 60),
                "/"
            );
        }

        // S'affiche pour tous
        // Affiche les informations et cache l'icon image manquante s'il n'y a pas d'image
        $content .= "
        <script>
              function hideImg() {
                document.getElementById('HideImg')
                   .style.display = 'none';
               }
        </script>
        <div>Nom de l'item : $i[nom] <br> Description : $i[descr] <br> Prix : $i[tarif] € <br> Détail du produit : <a href= '$i[url]'>$i[url]</a> <br></div>
        <div>Image:<br><img id=HideImg style='max-width: 200px' src='../../../../Ressources/img/$i[img]' onerror='hideImg()'></div><br>";

        //Affichage du formulaire si le nomReservation est null
        if ("$i[nomReservation]"== null && (!isset($_COOKIE["TokenEdition:".$tokenEdition]))) {
            $content .= "<form method='POST' action=''>
        <input type='text' name='nom' value='$champ' placeholder='nom'/><br>
        <textarea name='messageAuCreateur' placeholder='Message au createur' maxlength=255 cols=50 rows=8></textarea><br>";
            if ($i['estUneCagnotte'] == false) {
                $content .= "<button type='submit'>Réserver l'item</button>";
            } else {
                $content .= "L'objet est placé sous une cagnotte, vous pouvez choisir le montant de votre participation<br>
                Prix d'origine de l'objet : $i[tarif]€<br>Montant restant à régler : $i[tarif_restant]€<br>
                <input type='number' name='participation' step ='0.01' min='0.01' max=$i[tarif_restant] placeholder='participation'/><br>
                <button type='submit'>Participer à cette cagnotte</button>
                ";
            }
            $content .= "</form>";
        }
        
        //formulaire pour ajouter une image a l'item
        $tokenEdition = $l['token_edition'];
        if (isset($_COOKIE["TokenEdition:".$tokenEdition])) {
            $content .= "
            <form enctype='multipart/form-data' method='POST' action='' id='FormAjoutImageItem'>
            <br>
                <span>
                    <span>Ajouter une image de l'ordinateur à cet item (.jpg, .png ou .gif):</span>
                    <input type='file' name='image' placeholder='creaimage'></td>
                    <button type='submit'>Ajouter l'image</button>
                </span>
            </form>

            <form enctype='multipart/form-data' method='POST' action='' id='FormLinkImageItem'><br>
            <br>
                <span>Ajouter une image via un lien à cet item (.jpg, .png ou .gif):</span>
                    <input type='url' name='urlimage' placeholder='url_image' required></td>
                    <button type='submit' name='linkimage'>Ajouter l'image</button>
                </span>
            </form>

            <form enctype='multipart/form-data' method='POST' action='' id='FormSupprImageItem'><br>
            <br>
            <span>
                <button type='submit' name='supprimage'>X Supprimer l'image X</button>
            </span>
            </form>";
        }

        //Marque qui a reserve l'item : cela s'affiche seulement a ceux qui n'ont pas le token d'edition.
        // Celui qui a le token d'edition doit attendre que la date courante soit supérieur a la date d'expiration
        $content .= "</ul><hr>";
        if ((!isset($_COOKIE["TokenEdition:".$tokenEdition]))||(new \DateTime()) > $dateDExp) {
            if ($i['estUneCagnotte'] == 0) {
                // Ce n'est pas une cagnotte
                if ("$i[nomReservation]" != null) {
                    $content .= "L'item est reservé par : $i[nomReservation]<br>";
                    if ("$i[messageReservation]" != null) {
                        //Message au créateur si il y a un message et un nom de reservation
                        $content .= "Message : $i[messageReservation]<br>";
                    } else {
                        //Message au créateur si il n'y a pas de message et un nom de reservation
                        $content .= "Pas de message fournis lors de la réservation. <br>";
                    }
                } else {
                    if ("$i[messageReservation]" == null) {
                        $content .= "Pas de réservation. <br>";
                    }
                }
            } else {
                // C'est une cagnotte
                $arrayParticipants = $this->tab[2];
                if ($arrayParticipants == null) {
                    $content .= "Aucune participation pour le moment. <br>";
                } else {
                    foreach ($arrayParticipants as $p) {
                        $content .= "$p[nomParticipation] : $p[contribution]€<br>";
                        if ("$p[messageParticipation]" != null) {
                            //Message au créateur si il y a un message et un nom de reservation
                            $content .= "Message : $p[messageParticipation]<br><br>";
                        } else {
                            $content .= "Pas de message fournis lors de la participation.<br><br>";
                        }
                    }
                }
            }
        }

        // Modifier les informations de l'item
        if ((isset($_COOKIE["TokenEdition:".$tokenEdition]))&&("$i[nomReservation]"== null)&&((new \DateTime()) < $dateDExp) && ($i['tarif'] == $i['tarif_restant'])) {
            $content .= "Modifier les informations de l'item : (si l'item est réservé ou que vous ne possédez plus le token d'édition, cette action deviendra impossible)
        <form method='POST' action=''>
        <input type='text' name='nomItem'  placeholder='Nom de litem'/>
        <input type='number' name='tarifItem' step ='0.01' min='0.01' placeholder='Tarif de litem'/>
        <input type='url' name='modifurlItem' placeholder='url'/><br>
        <textarea name='descriItem' placeholder='Description de litem' maxlength=255 cols=50 rows=8></textarea><br>

        <button type='submit'>Modifier l'item</button>
        </form>";
        }

        if (isset($_COOKIE["TokenEdition:".$tokenEdition])&&(("$i[nomReservation]"!= null) || ($i['tarif'] != $i['tarif_restant']))&&(new \DateTime()) < $dateDExp) {
            $content .= "Vous ne pouvez plus modifier ou supprimer cet item car il est réservé, vous devez attendre la fin de la date d'expiration de votre liste pour voir qui a réservé l'item et le message laissé.";
        }

        //formulaire pour supprimer un item
        if (isset($_COOKIE["TokenEdition:".$tokenEdition])&&(("$i[nomReservation]"== null) || ($i['tarif'] == $i['tarif_restant']))&&(new \DateTime()) < $dateDExp) {
            $content .="<br>En guise de sécurité, pour supprimer l'item tapez ci-dessous : Je souhaite supprimer l'item
            <form method='POST' action=''>

            <input type='text' name='securiteSupprimerItem' placeholder='tapez ici'/>
            <button type='submit'>X Supprimer l'item X</button>
            </form>";
        }
        return "<section>$content</section>";
    }

    // Page qui s'affiche lorsque la liste ou l'item est inacessible, parce qu'elle est expiree ou pas encore publique
    private function htmlListeInacessible() : string
    {
        $l = $this->tab[0];
        $appel = $this->tab[2]; // vaut ../../ si l'appel viens d'un affichage item, vide sinon (pour le path)
        $content = "";
        $dateDExp = (new \DateTime("$l[expiration]"));
        $dateDExpString = $dateDExp->format('d-m-Y');
        $now = new \DateTime();
        $url_Accueil = $this->container->router->pathFor('Accueil');
        if ($now > $dateDExp) {
            $content .= "<h1>Cette liste est expirée</h1>
        <img style='max-width: 500px' src='$appel../../Ressources/img/end.jpg'>
        ";
        } else {
            $content .= "<h1>Cette liste n'a pas encore été rendu publique par son créateur</h1>
        <img style='max-width: 500px' src='$appel../../Ressources/img/soon.jpg'>
        ";
            setcookie(
                "TokenAcces:".$l['token'],
                $l['token'],
                strtotime($dateDExpString) + (24 * 60 * 60),
                "/"
            );
        }
        $content .= "<div><a href=$url_Accueil>Retour à l'accueil</a></div>";
        return "<section>$content</section>";
    }

    public function render($selecteur)
    {
        switch ($selecteur) {
        case 0: {
         $content = $this->htmlAccueil();
         break;
         }
         case 1: {
         $content = $this->htmlListes();
         break;
         }
         case 2: {
         $content = $this->htmlUneListe();
         break;
         }
         case 3: {
         $content = $this->htmlUnItem();
         break;
         }
         case 4: {
        $content = $this->htmlListeInacessible();
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
                <p><h1 id='wish'>My WishList</h1></p>
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
