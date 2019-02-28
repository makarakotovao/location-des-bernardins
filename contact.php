<?php
include('mailgun.php');


if (!empty($_POST['cdbmail']))
{
	$titre_mail = "";
	$message_erreur = "Erreur sur votre nom, veuillez recommencer";
	if (preg_match("#^[A-Za-z àáâãäåçèéêëìíîïðòóôõöùúûüýÿ'-]{3,50}$#", $_POST["nom"])) {
		$message_erreur = "Erreur sur votre mail, veuillez recommencer";
		if (preg_match("#^[\w.-]+@[\w.-]+\.[a-zA-Z1-9]{2,6}$#", $_POST["email"])) {
			$message_erreur = "Erreur sur votre numéro de téléphone, veuillez recommencer";
			if (preg_match("#^[ +(]{0,2}[0-9]{0,4}[)]?([-. ]?[0-9]{2,4}){3}[-. ]?[0-9]{0,3}[. ]?$#", $_POST["tel"])) {
				$message_erreur = "Veuillez ne pas inclure de '<' , '>' ou '/' dans vote message";
				if (!preg_match("#<|>|\/#", $_POST["message"])) {

					if (!empty($_POST['objet'])) {

						$titre_mail = "Merci pour votre message";
						$message_erreur = "nous vous adressons très vite une proposition pour votre événement";

						//require 'mail/class.mailer.php';

						$subject = "Form site : ".$_POST["nom"]."";

							$message  = '<html><body>';

								$message .= "<br><hr><font size=3>";

						 		$message .= "<br>Nom : <b>".$_POST["nom"]." - ".$_POST["prenom"]."</b><br>";
								$message .= "Adresse : <b>".$_POST["adresse"]." - ".$_POST["cp"]." - ".$_POST["ville"]." - ".$_POST["pays"]."</b><br>";
								
						 		$message .= "T&eacute;l&eacute;phones : <b>".$_POST["tel"]." - </b><br>";	
							 	$message .= "Mail : <b>".$_POST["email"]."</b><br><br>";		

								$message .= "Entreprise : <b>".$_POST["entreprise"]."</b>";	

								$message .= "<br><hr>";

								$message .= "<strong>Date de l'événement :</strong>".$_POST["date"]."<br>".$_POST["date2"]."<br>".$_POST["date3"]."<br>";

								$message .= $_POST["nmb_prs"]." personnes <br>";

								$message .= $_POST["quand"]."<br><br>";

								$message .= "Profil des invités : ".$_POST["profil_invit"]."<br>";

								$message .= "Objet de la manifestation : ".$_POST["objet"]."<br><br><hr>";

								$message .= "<strong>Message :</strong> <br><br>".$_POST["message"]."<br><br>";
							  
							$message .= "</font><hr><br><br>";

							$message .= '</body></html>';

						//$mailer = new Mailer();

						//$mailer->set_from('locationespace@collegedesbernardins.fr', 'Location des Bernardins');
						$from = 'Location des Bernardins <locationespace@collegedesbernardins.fr>';

						//$mailer->set_address('locationespace@collegedesbernardins.fr'); 
						//$mailer->set_address('EPC Team <ead@test.com>');  
						$to = 'locationespace@collegedesbernardins.fr'; 

/*
						$mailer->set_address(array(
						    'christophe.neto-paradela@collegedesbernardins.fr',
						), 'cc');
*/
						$bcc = 'christophe.neto-paradela@collegedesbernardins.fr, vincent@cstar.io';

/*
						$mailer->set_format('html');
						$mailer->set_charset('utf-8');
						$mailer->set_subject($subject);
						$mailer->set_message($message);
						$mailer->send();	
*/
    mg_mail($to, $subject,  $message, $from, $bcc);

						$cdbmail="";
					}
					else{

						$titre_mail = "Merci pour votre message";
						$message_erreur = "nous vous adressons très vite une proposition pour votre événement";

						//require 'mail/class.mailer.php';

						$subject = "Form site : ".$_POST["nom"]."";

							$message  = '<html><body>';

								$message .= "<br><hr><font size=3>";

						 		$message .= "<br>Nom : <b>".$_POST["nom"]." - ".$_POST["prenom"]."</b><br>";
								$message .= "Adresse : <b>".$_POST["adresse"]." - ".$_POST["cp"]." - ".$_POST["ville"]." - ".$_POST["pays"]."</b><br>";
								
						 		$message .= "T&eacute;l&eacute;phones : <b>".$_POST["tel"]." - </b><br>";	
							 	$message .= "Mail : <b>".$_POST["email"]."</b><br><br>";		

								$message .= "Entreprise : <b>".$_POST["entreprise"]."</b>";	

								$message .= "<br><hr>";
							
								$message .= "<strong>Message :</strong> <br><br>".$_POST["message"]."<br><br>";
							  
							$message .= "</font><hr><br><br>";

							$message .= '</body></html>';

						//$mailer = new Mailer();

						//$mailer->set_from('locationespace@collegedesbernardins.fr', 'Location des Bernardins');
						$from = 'Location des Bernardins <locationespace@collegedesbernardins.fr>';

						//$mailer->set_address('locationespace@collegedesbernardins.fr'); 
						$to = 'locationespace@collegedesbernardins.fr'; 
						//$mailer->set_address('EPC Team <ead@test.com>');  

/*
						$mailer->set_address(array(
						    'christophe.neto-paradela@collegedesbernardins.fr',
						), 'cc');
*/
						$bcc = 'christophe.neto-paradela@collegedesbernardins.fr, vincent@cstar.io';

/*
						$mailer->set_format('html');
						$mailer->set_charset('utf-8');
						$mailer->set_subject($subject);
						$mailer->set_message($message);
						$mailer->send();	
*/
    mg_mail($to, $subject,  $message, $from, $bcc);

						$cdbmail="";
					}
				}
			}
		}
	}
}

?>



<!DOCTYPE html>

<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->

<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->

<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->

<!--[if (gte IE 9)|!(IE)]><!-->

<html lang="fr">

<!--<![endif]-->

	<head>

		<!-- Basic Page Needs

		================================================== -->

		<meta charset="utf-8">

		<title>Location des bernardins</title>

		<!-- Mobile Specific Metas

		================================================== -->

		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

		<!-- CSS

		================================================== -->

		<!-- Bootstrap  -->

		<link type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.min.css">

		<!-- web font  -->

		<link href="http://fonts.googleapis.com/css?family=Open+Sans:300,400,600,800" rel="stylesheet" type="text/css">

		<!-- plugin css  -->

		<link rel="stylesheet" type="text/css" href="js-plugin/animation-framework/animate.css" />

		<link rel="stylesheet" type="text/css" href="js-plugin/magnific-popup/magnific-popup.css" />

		<link type="text/css" rel="stylesheet" href="js-plugin/isotope/css/style.css">

		<link rel="stylesheet" type="text/css" href="js-plugin/flexslider/flexslider.css" />

		<link rel="stylesheet" type="text/css" href="js-plugin/pageSlide/jquery.pageslide.css" />

		<!-- Owl carousel-->

		<link rel="stylesheet" href="js-plugin/owl.carousel/owl-carousel/owl.carousel.css">

		<link rel="stylesheet" href="js-plugin/owl.carousel/owl-carousel/owl.theme.css">

		<!-- appear-->

		<link rel="stylesheet" type="text/css" href="js-plugin/appear/nekoAnim.css">

		<!-- icon fonts -->

		<link type="text/css" rel="stylesheet" href="font-icons/custom-icons/css/custom-icons.css">

		<link type="text/css" rel="stylesheet" href="font-icons/custom-icons/css/custom-icons-ie7.css">

		<!-- Custom css -->

		<link type="text/css" rel="stylesheet" href="css/layout.css">

		<link type="text/css" id="colors" rel="stylesheet" href="css/purple.css">

		<link rel="stylesheet" type="text/css" href="css/style/styles.css">

		<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script> <![endif]-->

		<script src="js/modernizr-2.6.1.min.js"></script>

		<!-- Favicons

		================================================== -->

		<link rel="shortcut icon" href="images/favicon.ico">

		<link rel="apple-touch-icon" href="images/apple-touch-icon.png">

		<link rel="apple-touch-icon" sizes="72x72" href="images/apple-touch-icon-72x72.png">

		<link rel="apple-touch-icon" sizes="114x114" href="images/apple-touch-icon-114x114.png">

		<link rel="apple-touch-icon" sizes="144x144" href="images/apple-touch-icon-144x144.png">

	</head>

	<body data-spy="scroll" data-target="#scrollTarget" data-offset="150" class="activateAppearAnimation">

		<!-- Primary Page Layout 

		================================================== -->

		<!-- globalWrapper -->

		<div id="globalWrapper" class="localscroll">

			<!-- header -->

			<header id="mainHeader" class="navbar-fixed-top" role="banner">

				<div class="container">

					<nav class="navbar navbar-default scrollMenu" role="navigation">

						<div class="navbar-header">

							<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>

							<a href="/"><img src="images/logo/logo.gif" alt="College des bernardins - Location des espaces" id="logoLocation"/></a> </div>

							<div class="collapse navbar-collapse navbar-ex1-collapse" id="scrollTarget">

								<ul class="nav navbar-nav pull-right">

									<li><a href="index.html"><i class="glyphicon glyphicon-chevron-left"></i>Retour vers l'accueil</a></li>

								</ul>

							</div>



						</nav>

					</div>

				</header>

				<!-- header -->



				<section class="slice" id="about">

					<div class="container">

						<div class="row">

							<div class="col-xs-12">

								<h1><?php echo $titre_mail;?></h1>

								<h2 class="subTitle"><?php echo $message_erreur;?></h2>

							</div>

						</div>



						<div class="row">

							<img src="../images/college/auditorium.jpg" id="mecenat" data-nekoanim="fadeInRightBig" data-nekodelay="200">



							<div class="col-sm-6" data-nekoanim="fadeInRightBig" data-nekodelay="200">

								<h2>&nbsp;</h2>



								

						  </div>

						</div>



						<div class="row">

							<div class="col-sm-12" data-nekoanim="fadeInRightBig" data-nekodelay="300">

								<h2>&nbsp;</h2>

								<p>&nbsp;</p>

							</div>

							<div class="col-sm-12" data-nekoanim="fadeInRightBig" data-nekodelay="400">

								<h2>&nbsp;</h2>



								<p>&nbsp;</p>

							</div>

						</div>						

					</div>

				</section>



		</div>

						<!-- End Document 

		================================================== -->

		<script type="text/javascript" src="js-plugin/respond/respond.min.js"></script>

		<script type="text/javascript" src="js-plugin/jquery/1.8.3/jquery.min.js"></script>

		<script type="text/javascript" src="js-plugin/jquery-ui/jquery-ui-1.8.23.custom.min.js"></script>

		<!-- third party plugins  -->

		<script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>

		<script type="text/javascript" src="js-plugin/easing/jquery.easing.1.3.js"></script>



		<script type="text/javascript" src="js-plugin/flexslider/jquery.flexslider-min.js"></script>



		<script type="text/javascript" src="js-plugin/isotope/jquery.isotope.min.js"></script>

		<script type="text/javascript" src="js-plugin/neko-contact-ajax-plugin/js/jquery.form.js"></script>

		<script type="text/javascript" src="js-plugin/neko-contact-ajax-plugin/js/jquery.validate.min.js"></script>

		<script type="text/javascript" src="js-plugin/magnific-popup/jquery.magnific-popup.min.js"></script>

		<script type="text/javascript" src="js-plugin/parallax/js/jquery.scrollTo.2.0.0-min.js"></script>

		<script type="text/javascript" src="js-plugin/parallax/js/jquery.localscroll-1.2.7-min.js"></script>

		<script type="text/javascript" src="js-plugin/parallax/js/jquery.stellar.min.js"></script>

		<!-- appear -->

		<script type="text/javascript" src="js-plugin/appear/jquery.appear.js"></script>

		<script type="text/javascript" src="js-plugin/pageSlide/jquery.pageslide-custom.js"></script>

		<script type="text/javascript" src="js-plugin/jquery.sharrre-1.3.4/jquery.sharrre-1.3.4.min.js"></script>



		<script type="text/javascript" src="js-plugin/owl.carousel/owl-carousel/owl.carousel.min.js"></script>





		<!-- Custom  -->

		<script type="text/javascript" src="js/custom.js"></script>
        <script type="text/javascript" src="js/GogleAnalyticsObject.js"></script>		

	</body>

	</html>
