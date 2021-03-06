<?php
/**
 * Copyright (c) 2002-2006 Aur�lien Maille
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 * 
 * @package Wamailer
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wamailer/
 * @license http://www.gnu.org/copyleft/lesser.html	 GNU Lesser General Public License
 * @version 2.4
 */

if( !defined('CLASS_MAILER_INC') )
{

define('CLASS_MAILER_INC', true);
define('WM_HOST_OTHER',    1);
define('WM_HOST_ONLINE',   2);
define('WM_SMTP_MODE',     3);
define('WM_SENDMAIL_MODE', 4);

/**
 * Classe d'envois d'emails
 * 
 * Fonctionne �galement sur Online
 * G�re l'attachement de pi�ces jointes et l'envoi d'emails au format html, ainsi que les emails multi-formats.
 * G�re aussi les pi�ces jointes dites "embarqu�es"
 * (incorpor�es et utilis�es dans l'email html, ex: images, sons ..)
 * 
 * Se r�f�rer aux RFC 822, 2045, 2046, 2047 et 2822
 * 
 * Les sources qui m'ont bien aid�es :
 * 
 * @link http://abcdrfc.free.fr/ (fran�ais)
 * @link http://www.rfc-editor.org/ (anglais)
 * @link http://cvs.php.net/cvs.php/php4.fubar/ext/standard/mail.c?login=2
 * @link http://cvs.php.net/cvs.php/php4.fubar/win32/sendmail.c?login=2
 * 
 * @access public
 */
class Mailer {
	
	/************************ REGLAGES SMTP ************************/
	
	/**
	 * Activation du mode smtp
	 * 
	 * @var boolean
	 * @access public
	 */
	var $smtp_mode             = false;
	
	/**
	 * Chemin vers la classe smtp
	 * 
	 * Si laiss�e vide, le script tentera de reconstituer le chemin vers la classe smtp
	 * (la classe smtp doit alors �tre dans le m�me dossier que la pr�sente classe)
	 * 
	 * @var string
	 * @access public
	 */
	var $smtp_path             = '';
	
	/**
	 * Variable qui contiendra l'objet smtp
	 * 
	 * @var object
	 * @access public
	 */
	var $smtp                  = null;
	
	/**
	 * Si plac� � TRUE, la connexion au serveur SMTP ne sera pas ferm�e apr�s l'envoi, et sera r�utilis�e 
	 * pour un envoi ult�rieur. 
	 * Ce sera alors au programmeur de refermer lui m�me la connexion apr�s la fin des envois en faisant 
	 * appel � la m�thode quit() de la classe smtp : $mailer->smtp->quit(); 
	 * 
	 * @var boolean
	 * @access public
	 */
	var $persistent_connection = false;
	
	/***************************************************************/
	
	/********************** REGLAGES SENDMAIL **********************/
	
	/**
	 * Activation du mode sendmail
	 * 
	 * @var boolean
	 * @access public
	 */
	var $sendmail_mode          = false;
	
	/**
	 * Chemin d'acc�s � sendmail
	 * 
	 * @var string
	 * @access public
	 */
	var $sendmail_path          = '/usr/sbin/sendmail';
	
	/**
	 * Param�tres de commandes compl�mentaires
	 * 
	 * @var string
	 * @access public
	 */
	var $sendmail_cmd           = '';
	
	/***************************************************************/
	
	/**
	 * Chemins par d�faut pour les mod�les d'emails 
	 *
	 * @var string
	 * @access public
	 */
	var $root                   = './';
	
	/**
	 * Extensions des mod�les au format texte
	 *
	 * @var string
	 * @access public
	 */
	var $text_tpl_ext           = 'txt';
	
	/**
	 * Extensions des mod�les au format html
	 *
	 * @var string
	 * @access public
	 */
	var $html_tpl_ext           = 'html';
	
	/**
	 * Vous devez d�finir la fonction mail qu'utilise votre h�bergeur 
	 * 
	 * 1 ou WM_HOST_OTHER pour la fonction mail() classique
	 * 2 ou WM_HOST_ONLINE pour la fonction email() de online
	 *
	 * @var integer
	 * @access public
	 */
	var $hebergeur              = WM_HOST_OTHER;
	
	/**
	 * Format de l'email 
	 * 
	 * 1 - pour format texte brut
	 * 2 - pour format html
	 * 3 - Multi-format (html affich�, et texte si html pas support�)
	 * 
	 * @var integer
	 * @access public
	 */
	var $format                 = 1;
	
	/**
	 * Adresse de l'exp�diteur 
	 * 
	 * @var array
	 * @access public
	 */
	var $sender                 = '';
	
	/**
	 * Tableau des destinataires
	 * 
	 * @var array
	 * @access private
	 */
	var $address                = array('To' => array(), 'Cc' => array(), 'Bcc' => array());
	
	/**
	 * Sujet de l'email
	 * 
	 * @var string
	 * @access public
	 */
	var $subject                = '';
	
	/**
	 * Messages non compil�s, selon le format
	 * 
	 * @var array
	 * @access private
	 */
	var $uncompiled_message     = array();
	
	/**
	 * Messages alternatifs non compil�s, selon le format
	 * 
	 * @var array
	 * @access private
	 */
	var $uncompiled_altmessage  = array();
	
	/**
	 * Messages compil�s, selon le format
	 * 
	 * @var array
	 * @access private
	 */
	var $compiled_message       = array();
	
	/**
	 * Tableau des tags � remplacer dans le message
	 * 
	 * @var array
	 * @access private
	 */
	var $tags                   = array();
	
	/**
	 * Tableau des blocks � remplacer dans le message, ainsi que les tags qui leur sont associ�s
	 * 
	 * @var array
	 * @access private
	 */
	var $block_tags             = array();
	
	/**
	 * "Fronti�res" utilis�es pour s�parer les diff�rentes parties de l'email
	 * 
	 * @var array
	 * @access private
	 */
	var $boundary               = array('part0' => array(), 'part1' => array(), 'part2' => array());
	
	/**
	 * Tableau des fichiers attach�s
	 * 
	 * @var array
	 * @access private
	 */ 
	var $attachfile             = array('path' => array(), 'name' => array(), 'mimetype' => array(), 'disposition' => array());
	
	/**
	 * Tableau des fichiers incorpor�s (sp�cifique aux emails au format html)
	 * 
	 * @var array
	 * @access private
	 */
	var $embeddedfile           = array('path' => array(), 'name' => array(), 'mimetype' => array());
	
	/**
	 * Extraction automatique des images et autres objets pr�sents dans le
	 * corps html de l'email et ajout dans le table $embeddedfile
	 * 
	 * @see ligne 2178
	 * @var boolean
	 * @access public
	 */
	var $extract_auto           = false;
	
	/**
	 * Tableau des en-t�tes de l'email
	 * 
	 * @var array
	 * @access private
	 */
	var $headers                = array();
	
	/**
	 * Jeu de caract�re utilis� dans l'email
	 * 
	 * @var string
	 * @access public
	 */
	var $charset                = 'iso-8859-1';
	
	/**
	 * Encodage � utiliser 
	 * (7bit, 8bit, quoted-printable, base64 ou binary)
	 * 
	 * @var string
	 * @access public
	 */
	var $encoding               = '8bit';
	
	/**
	 * Utilis� dans la m�thode Mailer::encode_mime_header() pour d�tecter
	 * les octets de d�but de s�quence dans une cha�ne utf-8
	 * 
	 * @var array
	 * @access private
	 */
	var $_utf8test              = array(
		0x80 => 0, 0xE0 => 0xC0, 0xF0 => 0xE0, 0xF8 => 0xF0, 0xFC => 0xF8, 0xFE => 0xFC
	);
	
	/**
	 * Longueur maximale des lignes dans l'email, telle que d�finie dans la rfc2822
	 * 
	 * @var integer
	 * @access private
	 */
	var $maxlen                 = 78;
	
	/**
	 * IP de l'exp�diteur
	 * 
	 * @var string
	 * @access public
	 */
	var $sender_ip              = '127.0.0.1';
	
	/**
	 * Nom du serveur �metteur
	 * 
	 * @var string
	 * @access public
	 */
	var $server_from            = 'localhost';
	
	/**
	 * Activer/d�sactiver le validateur d'adresse email
	 * 
	 * @var boolean
	 * @access public
	 */
	var $valid_syntax           = false;
	
	/**
	 * Activer/d�sactiver le mode de d�bogguage
	 * S'il est activ�, les messages d'erreur s'afficheront directement � l'�cran et l'�x�cution du script 
	 * sera interrompu
	 * 
	 * @var boolean
	 * @access public
	 */
	var $debug                  = false;
	
	/**
	 * Statut du traitement de l'envoi
	 * Cette variable ne doit pas �tre modifi�e, si elle est � false, 
	 * l'email n'est tout simplement pas envoy�
	 * 
	 * @var boolean
	 * @access private
	 */
	var $statut                 = true; // ne pas modifier !
	
	/**
	 * Variable contenant le dernier message d'erreur
	 * 
	 * @var string
	 * @access private
	 */
	var $msg_error              = '';
	
	/**
	 * Pour comprendre l'utilit� de cette variable, r�f�rez vous � la m�thode recipients_list()
	 * 
	 * Si votre serveur utilise sendmail, mettez � 1, s'il utilise un serveur smtp, mettez � -1.
	 * Si vous ne savez pas, n'y touchez pas, le script tentera de trouver de lui m�me.
	 * 
	 * @var mixed
	 * @access public
	 */
	var $fix_bug_mail           = null;
	
	/**
	 * Version actuelle de la classe
	 * 
	 * @var string
	 * @access private
	 */
	var $version                = '2.4';
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $template_path  Chemin vers les mod�les d'emails 
	 * 
	 * @access public
	 * @return void
	 */
	function Mailer($template_path = '')
	{
		if( $template_path != '' )
		{
			$this->set_root($template_path);
		}
		
		//
		// On r�cup�re le domaine actuel dans le cas d'un dialogue SMTP
		// et pour certains en-t�tes de l'email
		//
		if( $this->server_from == 'localhost' && !empty($_SERVER['SERVER_NAME']) )
		{
			$this->server_from = $_SERVER['SERVER_NAME'];
		}
		
		//
		// On r�cup�re l'adresse IP pour l'en-t�te abuse
		//
		if( $this->sender_ip == '127.0.0.1' && !empty($_SERVER['REMOTE_ADDR']) )
		{
			$this->sender_ip = $_SERVER['REMOTE_ADDR'];
			
			if( preg_match('/^\d+\.\d+\.\d+\.\d+/', getenv('HTTP_X_FORWARDED_FOR'), $match) ) 
			{
				$private_ip = $match[0];
				
				/**
				 * Liens utiles sur les diff�rentes plages d'ip :
				 * 
				 * @link http://www.commentcamarche.net/internet/ip.php3
				 * @link http://www.usenet-fr.net/fur/comp/reseaux/masques.html
				 */ 
				
				// 
				// Liste d'ip non valides
				// 
				$pattern_ip   = array();
				$pattern_ip[] = '/^0\..*/'; // R�seau 0 n'existe pas
				$pattern_ip[] = '/^127\.0\.0\.1/'; // ip locale
				
				// Plages d'ip sp�cifiques � l'intranet
				$pattern_ip[] = '/^10\..*/';
				$pattern_ip[] = '/^172\.1[6-9]\..*/';
				$pattern_ip[] = '/^172\.2[0-9]\..*/';
				$pattern_ip[] = '/^172\.30\..*/';
				$pattern_ip[] = '/^172\.31\..*/';
				$pattern_ip[] = '/^192\.168\..*/';
				
				// Plage d'adresse de classe D r�serv�e pour les flux multicast et de classe E, non utilis�e 
				$pattern_ip[] = '/^22[4-9]\..*/';
				$pattern_ip[] = '/^2[3-5][0-9]\..*/';
				
				$this->sender_ip = preg_replace($pattern_ip, $this->sender_ip, $private_ip);
			}
		}
		
		if( $this->hebergeur == WM_HOST_OTHER && Mailer::is_online_host() == true )
		{
			$this->hebergeur = WM_HOST_ONLINE;
		}
	}
	
	/**
	 * Indique si l'on est sur un serveur de l'h�bergeur Online
	 * 
	 * @access public
	 * @return boolean
	 */
	function is_online_host()
	{
		return (function_exists('email') && !function_exists('mail'));
	}
	
	/**
	 * Initialise un objet Smtp pour utilisation ult�rieure
	 * 
	 * @param string  $server  Nom du serveur SMTP
	 * @param integer $port    Port de connexion (25 dans la grande majorit� des cas)
	 * @param string  $user    Login d'authentification (si AUTH est support� par le serveur)
	 * @param string  $pass    Password d'authentification (si AUTH est support� par le serveur)
	 * @param string  $server_from  Serveur �metteur
	 * 
	 * @access public
	 * @return void
	 */
	function use_smtp($server = '', $port = 25, $user = '', $pass = '', $server_from = '')
	{
		$this->smtp_mode = true;
		$this->hebergeur = WM_SMTP_MODE;
		
		$smtp = $this->init_smtp($this->debug);
		
		if( $server_from != '' )
		{
			$this->server_from = $smtp->server_from = $server_from;
		}
		
		foreach( array('server', 'port', 'user', 'pass') as $varname )
		{
			if( !empty(${$varname}) )
			{
				$smtp->{'smtp_'.$varname} = ${$varname};
			}
		}
		
		$this->smtp = $smtp;
	}
	
	/**
	 * Cr�� une nouvelle instance de la classe
	 * 
	 * @param boolean $debug
	 * 
	 * @access private
	 * @return object
	 */
	function init_smtp($debug = false)
	{
		if( !class_exists('Smtp') )
		{
			if( isset($this) && !empty($this->smtp_path) )
			{
				$smtp_path = rtrim($this->smtp_path, '/');
			}
			else
			{
				$smtp_path = dirname(__FILE__);
			}
			
			require $smtp_path . '/class.smtp.php';
		}
		
		$smtp = new Smtp();
		$smtp->debug = $debug;
		
		return $smtp;
	}
	
	/**
	 * Param�trage des variables concernant sendmail
	 * 
	 * @param string $sendmail_cmd
	 * @param string $sendmail_path
	 * 
	 * @access public
	 * @return boolean
	 */
	function use_sendmail($sendmail_cmd = '', $sendmail_path = '')
	{
		$this->sendmail_mode = true;
		$this->hebergeur     = WM_SENDMAIL_MODE;
		
		$this->sendmail_path = ( $sendmail_path != '' ) ? $sendmail_path : $this->sendmail_path;
		$this->sendmail_cmd  = ( $sendmail_cmd != '' ) ? $sendmail_cmd : $this->sendmail_cmd;
		
		if( !@is_executable($this->sendmail_path) )
		{
			$this->error('use_sendmail() :: ' . $this->sendmail_path . ' n\'est pas ex�cutable');
			return false;
		}
		
		return true;
	}
	
	/**
	 * R�glages du chemin vers les mod�les
	 * 
	 * @param string $template_path  Chemin vers les mod�les
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_root($template_path)
	{
		$template_path = rtrim($template_path, '/');
		
		if( !is_dir($template_path) )
		{
			$this->error("set_root() :: Le chemin \"$template_path/\" est incorrect.");
			return false;
		}
		
		$this->root = $template_path . '/';
		
		return true;
	}
	
	/**
	 * V�rifie qu'un r�pertoire ou fichier existe et est accessible en lecture
	 * 
	 * @param string $path  Chemin vers le fichier
	 * 
	 * @access private
	 * @return boolean
	 */
	function set_file($path)
	{
		if( is_readable($path) )
		{
			return true;
		}
		
		$this->error('set_file() :: Le fichier "' . basename($path) . '" est introuvable ou n\'est pas accessible en lecture');
		return false;
	}
	
	/**
	 * Retourne le contenu d'un fichier
	 * 
	 * @param string  $path         Chemin vers le fichier
	 * @param boolean $binary_file  On sp�cifie si on charge un fichier binaire (pour windows)
	 * 
	 * @access public
	 * @return mixed
	 */
	function loadfile($path, $binary_file = false)
	{
		$mode = ( $binary_file ) ? 'rb' : 'r';
		
		if( !($fp = @fopen($path, $mode)) )
		{
			$this->error('loadfile() :: Lecture du fichier "' . basename($path) . '" impossible');
			return false;
		}
		
		$contents = fread($fp, filesize($path));
		fclose($fp);
		
		return $contents;
	}
	
	/**
	 * @param mixed $format  Format de l'email
	 * 
	 * @access public
	 * @return void
	 */
	function set_format($format)
	{
		if( !is_numeric($format) )
		{
			$format = strtolower($format);
		}
		
		switch( $format )
		{
			case 'alt':
			case 3:
				$this->format = 3;
				break;
			
			case 'html':
			case 'htm':
			case 2:
				$this->format = 2;
				break;
			
			case 'texte':
			case 'txt':
			case 'text':
			case 1:
			default:
				$this->format = 1;
				break;
		}
	}
	
	/**
	 * @param string $charset
	 * 
	 * @access public
	 * @return void
	 */
	function set_charset($charset)
	{
		$this->charset = $charset;
	}
	
	/**
	 * V�rifie la validit� syntaxique d'une adresse email
	 * 
	 * @param string $email
	 * 
	 * @access public
	 * @return boolean
	 */
	function validate_email($email)
	{
		return (bool) preg_match('/^((?(?<!^)\.)[-!#$%&\'*+\/0-9=?a-z^_`{|}~]+)+@'
			. '((?(?<!@)\.)[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?)+$/i', $email);
	}
	
	/**
	 * V�rifie si une adresse email N'EST PAS valide (domaine et compte).
	 * Ceci est diff�rent d'une v�rification de validit�.
	 * Le serveur SMTP peut tr�s bien r�pondre par un 250 ok pour cet email,
	 * les erreurs d'adressage �tant trait�es ult�rieurement au niveau du
	 * serveur POP.
	 * 
	 * @link http://www.sitepoint.com/article/1051
	 * @link http://www.zend.com/codex.php?id=449&single=1
	 * @link http://fr.php.net/getmxrr (troisi�me User contributed note)
	 * 
	 * @param string $email   Adresse email � v�rifier
	 * @param string $errstr  Pass� par r�f�rence. Contiendra l'�ventuel message
	 *                        d'erreur retourn� par le serveur SMTP
	 * 
	 * @access public
	 * @return boolean
	 */
	function validate_email_mx($email, &$errstr)
	{
		$result_check_mx = true;
		
		list(, $domain) = explode('@', $email);
		
		$mx = array();
		if( !function_exists('getmxrr') )
		{
			exec(sprintf('nslookup -type=mx %s', escapeshellarg($domain)), $lines);
			
			$regexp = '/^' . preg_quote($domain) . '\s+(?:(?i)MX\s+)?'
				. '(preference\s*=\s*([0-9]+),\s*)?'
				. 'mail\s+exchanger\s*=\s*(?(1)|([0-9]+)\s+)([^ ]+?)\.?$/';
			
			foreach( $lines as $value )
			{
				if( preg_match($regexp, $value, $match) )
				{
					array_push($mx, array(
						$match[3] === '' ? $match[2] : $match[3],
						$match[4]
					));
				}
			}
			
			$result = ( count($mx) > 0 ) ? true : false;
		}
		else
		{
			$result = getmxrr($domain, $hosts, $weight);
			
			for( $i = 0, $m = count($hosts); $i < $m; $i++ )
			{
				array_push($mx, array($weight[$i], $hosts[$i]));
			}
		}
		
		if( !$result )
		{
			array_push($mx, array(0, $domain));
		}
		
		array_multisort($mx);
		
		$smtp = Mailer::init_smtp(false);
		
		foreach( $mx as $record )
		{
			if( $smtp->connect($record[1]) )
			{
				if( $smtp->mail_from($email) )
				{
					if( !$smtp->rcpt_to($email, true) )
					{
						$errstr = $smtp->reponse;
						$result_check_mx = false;
					}
				}
				
				$smtp->quit();
				break;
			}
			else if( !$result )
			{
				$errstr = $smtp->msg_error;
				$result_check_mx = false;
				break;
			}
		}
		
		return $result_check_mx;
	}
	
	/**
	 * D�finition du champ exp�diteur
	 * 
	 * @param string $email  Email de l'exp�diteur
	 * @param string $name   Personnalisation du nom de l'exp�diteur
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_from($email, $name = '')
	{
		if( $this->valid_syntax && !$this->validate_email($email) )
		{
			$this->error('set_from() :: "' . $email . '", cette adresse email n\'est pas valide');
			return false;
		}
		
		$this->sender = trim($email);
		$this->headers['From'] = $this->sender;
		if( $name != '' )
		{
			$this->headers['From'] = sprintf('%s <%s>',
				$this->encode_mime_header($name, 'from', 'phrase'), $this->sender);
		}
		
		return true;
	}
	
	/**
	 * D�finition des destinataires
	 * 
	 * @param mixed  $email_mixed  Email du destinataire ou tableau contenant la liste des destinataires 
	 * @param string $type         Type de destinataire : to, cc, ou bcc
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_address($email_mixed, $type = '')
	{
		$type = ucfirst(strtolower($type));
		if( $type != 'Cc' && $type != 'Bcc' )
		{
			$type = 'To';
		}
		
		if( !is_array($email_mixed) )
		{
			$email_mixed = trim($email_mixed);
			if( preg_match('/^([^<]*)<([^>]+)>$/', $email_mixed, $regs) )
			{
				if( !empty($regs[1]) )
				{
					$regs[1] = trim($regs[1], '"');
				}
				else
				{
					$regs[1] = 0;
				}
				$email_mixed = array($regs[1] => $regs[2]);
			}
			else
			{
				$email_mixed = array($email_mixed);
			}
		}
		
		foreach( $email_mixed as $name => $email )
		{
			$email = trim($email);
			
			if( $this->valid_syntax && !$this->validate_email($email) )
			{
				$this->error('set_address() :: "' . $email . '", cette adresse email n\'est pas valide');
				return false;
			}
			
			$this->address[$type][] = $email;
			$name = ( !is_numeric($name) ) ? trim($name) : '';
			
			if( !empty($this->headers[$type]) )
			{
				$this->headers[$type] .= ', ';
			}
			else
			{
				$this->headers[$type] = '';
			}
			
			if( $name != '' )
			{
				$email = sprintf('%s <%s>',
					$this->encode_mime_header($name, $type, 'phrase'), $email);
			}
			
			$this->headers[$type] .= $email;
		}
		
		return true;
	}
	
	/**
	 * Ancienne m�thode d'ajout de destinataires, pr�sent pour assurer la compatibilit� 
	 * 
	 * @see Mailer::set_address()
	 * @access public
	 * @status obsolete
	 * @return boolean
	 */
	function set_to($arg1, $arg2 = '')
	{
		return $this->set_address($arg1, $arg2);
	}
	
	/**
	 * D�finition du sujet de l'email
	 * 
	 * @param string $subject  Le sujet de l'email
	 * 
	 * @access public
	 * @return void
	 */
	function set_subject($subject)
	{
		$this->subject = trim($this->encode_mime_header($subject, 'subject'));
	}
	
	/**
	 * Corps de l'email
	 * 
	 * @param string $message  Contient le message � envoyer
	 * @param array  $tags     Variables � remplacer dans le texte
	 * 
	 * @access public
	 * @return void
	 */
	function set_message($message, $tags = null)
	{
		$this->compiled_message[$this->format]   = '';
		$this->uncompiled_message[$this->format] = $message;
		
		$this->assign_tags($tags);
	}
	
	/**
	 * Alternative texte de l'email (on suppose que set_message() a �t� appell�
	 * avec un contenu html)
	 * 
	 * @param string $message  Contient le message alternatif
	 * @param array  $tags     Variables � remplacer dans le texte
	 * 
	 * @access public
	 * @return void
	 */
	function set_altmessage($message, $tags = null)
	{
		$this->uncompiled_altmessage[$this->format] = $message;
		
		$this->assign_tags($tags);
	}
	
	/**
	 * @param string $file  Nom du mod�le (sans l'extension)
	 * @param array  $tags  Variables � remplacer dans le texte
	 * 
	 * @access public
	 * @return boolean
	 */
	function use_template($file, $tags = null)
	{
		$this->compiled_message[$this->format] = '';
		
		if( !$this->set_root($this->root) )
		{
			return false;
		}
		
		if( ( $this->format == 3 || $this->format == 1 ) && $this->set_file($this->root . $file . '.' . $this->text_tpl_ext) )
		{
			$eval  = '$this->uncompiled_' . ( ( $this->format == 3 ) ? 'altmessage' : 'message' );
			$eval .= '[$this->format] = $this->loadfile($this->root . $file . \'.' . $this->text_tpl_ext . '\');';
			
			eval($eval);
		}
		
		if( ( $this->format == 3 || $this->format == 2 ) && $this->set_file($this->root . $file . '.' . $this->html_tpl_ext) )
		{
			$this->uncompiled_message[$this->format] = $this->loadfile($this->root . $file . '.' . $this->html_tpl_ext);
		}
		
		$this->assign_tags($tags);
		
		return true;
	}
	
	/**
	 * @param array $tags  Tableau des tags � remplacer dans le message
	 * 
	 * @access public
	 * @return void
	 */
	function assign_tags($tags)
	{
		if( is_array($tags) )
		{
			foreach( $tags as $key => $val )
			{
				if( preg_match('/^[[:alnum:]_-]+$/i', $key) )
				{
					$this->tags[$key] = $val;
				}
			}
		}
	}
	
	/**
	 * @param string $name  Nom du block et des �ventuels sous blocks
	 * @param array  $tags  Tableau des tags � remplacer dans le message
	 * 
	 * @access public
	 * @return void
	 */
	function assign_block_tags($name, $tags = null)
	{
		if( preg_match('/^[[:alnum:]_-]+$/i', $name) )
		{
			$this->block_tags[$name] = array();
			
			if( is_array($tags) )
			{
				foreach( $tags as $key => $val )
				{
					if( preg_match('/^[[:alnum:]_-]+$/i', $key) )
					{
						$this->block_tags[$name][$key] = $val;
					}
				}
			}
		}
	}
	
	/**
	 * Ajout d'un fichier joint
	 * 
	 * @param string  $path         Chemin vers le fichier
	 * @param string  $filename     Nom du fichier
	 * @param string  $disposition  Disposition
	 * @param string  $mime_type    Type de m�dia
	 * @param boolean $embedded     true si fichier incorpor� dans l'email html
	 * 
	 * @access public
	 * @return boolean
	 */
	function attachment($path, $filename = '', $disposition = '', $mime_type = '', $embedded = false)
	{
		$this->compiled_message[$this->format] = '';
		
		if( !$this->set_file($path) )
		{
			return false;
		}
		
		$name = ( $filename != '' ) ? $filename : basename($path);
		
		if( $embedded )
		{
			$offset = count($this->embeddedfile['path']);
			
			$this->embeddedfile['path'][$offset]     = $path;
			$this->embeddedfile['name'][$offset]     = $name;
			$this->embeddedfile['mimetype'][$offset] = $mime_type;
		}
		else
		{
			$offset = count($this->attachfile['path']);
			
			$this->attachfile['path'][$offset]        = $path;
			$this->attachfile['name'][$offset]        = $name;
			$this->attachfile['mimetype'][$offset]    = $mime_type;
			$this->attachfile['disposition'][$offset] = ( $disposition == 'inline' ) ? 'inline' : 'attachment';
		}
		
		return true; 
	}
	
	/**
	 * Renvoie le type mime � partir de l'extension de fichier
	 * 
	 * @param string $ext  Extension de fichier
	 * 
	 * @access public
	 * @return string
	 */
	function mime_type($ext)
	{
		//
		// Tableau des extensions et de leur Mime-Type
		// Rien ne vous interdit d'en rajouter si besoin est.
		//
		$mime_type_ary = array(
			'css'  => 'text/css',
			'html' => 'text/html',
			'htm'  => 'text/html',
			'js'   => 'text/javascript',
			'txt'  => 'text/plain',
			'rtx'  => 'text/richtext',
			'tsv'  => 'text/tab-separated-value',
			'xml'  => 'text/xml',
			'xls'  => 'text/xml',
			
			'eml'  => 'message/rfc822',
			'nws'  => 'message/rfc822',
			
			'bmp'  => 'image/bmp',
			'pcx'  => 'image/bmp',
			'gif'  => 'image/gif',
			'ief'  => 'image/ief',
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'jpe'  => 'image/jpeg',
			'png'  => 'image/png',
			'tiff' => 'image/tiff',
			'tif'  => 'image/tiff',
			'cmu'  => 'image/x-cmu-raster',
			'pnm'  => 'image/x-portable-anymap',
			'pbm'  => 'image/x-portable-bitmap',
			'pgm'  => 'image/x-portable-graymap',
			'ppm'  => 'image/x-portable-pixmap',
			'rgb'  => 'image/x-rgb',
			'xbm'  => 'image/x-xbitmap',
			'xpm'  => 'image/x-xpixmap',
			'xwd'  => 'image/x-xwindowdump',
			
			'dwg'  => 'application/acad',
			'ccad' => 'application/clariscad',
			'drw'  => 'application/drafting',
			'dxf'  => 'application/dxf',
			'xls'  => 'application/excel',
			'hdf'  => 'application/hdf',
			'unv'  => 'application/i-deas',
			'igs'  => 'application/iges',
			'iges' => 'application/iges',
			'doc'  => 'application/msword',
			'dot'  => 'application/msword',
			'wrd'  => 'application/msword',
			'oda'  => 'application/oda',
			'pdf'  => 'application/pdf',
			'ppt'  => 'application/powerpoint',
			'ai'   => 'application/postscript',
			'eps'  => 'application/postscript',
			'ps'   => 'application/postscript',
			'rtf'  => 'application/rtf',
			'rm'   => 'application/vnd.rn-realmedia',
			'dvi'  => 'application/x-dvi',
			'gtar' => 'application/x-gtar',
			'tgz'  => 'application/x-gtar',
			'swf'  => 'application/x-shockwave-flash',
			'tar'  => 'application/x-tar',
			'gz'   => 'application/x-gzip-compressed',
			'zip'  => 'application/zip',
			'xhtml'=> 'application/xhtml+xml',
			'xht'  => 'application/xhtml+xml',
			
			'au'   => 'audio/basic',
			'snd'  => 'audio/basic',
			'aif'  => 'audio/x-aiff',
			'aiff' => 'audio/x-aiff',
			'aifc' => 'audio/x-aiff',
			'wma'  => 'audio/x-ms-wma',
			
			'mpeg' => 'video/mpeg',
			'mpg'  => 'video/mpeg',
			'mpe'  => 'video/mpeg',
			'mov'  => 'video/quicktime',
			'avi'  => 'video/msvideo',
			'movie'=> 'video/x-sgi-movie',
			
			'unknow' => 'application/octet-stream'
		);
		
		return ( !empty($mime_type_ary[$ext]) ) ? $mime_type_ary[$ext] : $mime_type_ary['unknow'];
	}
	
	/**
	 * D�finition de l'adresse de r�ponse
	 * 
	 * @param string $email  Email de r�ponse
	 * @param string $name   Personnalisation
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_reply_to($email = '', $name = '')
	{
		if( $email != '' )
		{
			$email = trim($email);
			$name  = trim($name);
			
			if( $this->valid_syntax && !$this->validate_email($email) )
			{
				$this->error('set_reply_to() :: "' . $email . '", cette adresse email n\'est pas valide');
				return false;
			}
			
			if( $name != '' )
			{
				$email = sprintf('%s <%s>',
					$this->encode_mime_header($name, 'Reply-To', 'phrase'), $email);
			}
		}
		else
		{
			$email = $this->headers['From'];
		}
		
		$this->headers['Reply-To'] = $email;
				
		return true;
	}
	
	/**
	 * D�finition de l'adresse de retour d'erreurs
	 * 
	 * @param string $email  Email de retour d'erreur
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_return_path($email = '')
	{
		$email = trim($email);
		
		if( $email != '' )
		{
			if( $this->valid_syntax && !$this->validate_email($email) )
			{
				$this->error('set_return_path() :: "' . $email . '", cette adresse email n\'est pas valide');
				return false;
			}
		}
		else
		{
			$email = $this->sender;
		}
		
		$this->headers['Return-Path'] = $email;
		
		return true;
	}
	
	/**
	 * D�finition de l'adresse cible pour les notifications de lecture
	 * 
	 * @param string $email  Email pour le retour de notification de lecture 
	 *                       (par d�faut, l'adresse d'envoi est utilis�e)
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_notify($email = '')
	{
		if( $email != '' )
		{
			$email = trim($email);
			
			if( $this->valid_syntax && !$this->validate_email($email) )
			{
				$this->error('set_notify() :: "' . $email . '", cette adresse email n\'est pas valide');
				return false;
			}
		}
		else
		{
			$email = $this->sender;
		}
		
		$this->headers['Disposition-Notification-To'] = '<' . $email . '>';
		
		return true;
	}
	
	/**
	 * @param string $soc
	 * 
	 * @access public
	 * @return void
	 */
	function organization($soc)
	{
		$this->headers['Organization'] = $this->encode_mime_header($soc, 'Organization');
	}
	
	/**
	 * Priorit� de l'email
	 * 
	 * @param mixed $level  Niveau de priorit� de l'email
	 * 
	 * @access public
	 * @return void
	 */
	function set_priority($level)
	{
		if( is_numeric($level) )
		{
			if( $level > 0 && $level <= 5 )
			{
				$this->headers['X-Priority'] = $level;
			}
		}
		else
		{
			$level = strtolower($level);
			
			if( !in_array($level, array('highest', 'high', 'low', 'lowest')) )
			{
				$level = 'normal';
			}
			
			$this->headers['X-MSMail-Priority'] = ucfirst($level);
		}
	}
	
	/**
	 * Ajout d'en-t�tes suppl�mentaires
	 * 
	 * @param string $name   Nom de l'ent�te 
	 * @param string $value  Contenu de l'ent�te
	 * 
	 * @access public
	 * @return boolean
	 */
	function additionnal_header($name, $value)
	{
		//
		// Le nom de l'en-t�te ne doit contenir que des caract�res us-ascii, 
		// et ne doit pas contenir le caract�re deux points (:)
		// - Section 2.2 de la rfc 2822
		//
		if( !preg_match('/^[\x21-\x39\x3B-\x7E]+$/', $name) )
		{
			return false;
		}
		
		$name  = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
		
		//
		// Le contenu de l'en-t�te ne doit contenir aucun retour chariot ou 
		// saut de ligne
		// - Section 2.2 de la rfc 2822
		//
		$value = preg_replace('/[\x0A\x0D]/', '', trim($value));
		
		$this->headers[$name] = $value;
		
		return true;
	}
	
	/**
	 * @param string $encoding  Type d'encodage d�sir�
	 * @param string $str       Cha�ne � encoder
	 * 
	 * @access private
	 * @return string
	 */
	function make_encoding($encoding, $str)
	{
		switch( $encoding )
		{
			case '7bit':
			case '8bit':
				$str = preg_replace("/\r\n?/", "\n", $str);
				$str = $this->word_wrap($str, false);
				break;
			
			/**
			 * Encodage quoted-printable
			 * @link http://jlr31130.free.fr/rfc2045.html#6.7.
			 */
			case 'quoted-printable':
				$str = $this->quoted_printable_encode($str);
				break;
			
			/**
			 * Encodage en base64
			 * @link http://jlr31130.free.fr/rfc2045.html#6.8.
			 */
			case 'base64':
				$str = rtrim(chunk_split(base64_encode($str), 76, "\n"));
				break;
			
			case 'binary':
				break;
			
			default:
				$this->error('make_encoding() :: Aucun encodage valide sp�cifi� !');
				break;
		}
		
		return $str;
	}
	
	/**
	 * Encode le texte en cha�ne � guillemets
	 * 
	 * @param string $str  Texte � encoder
	 * 
	 * @access private
	 * @return string
	 */
	function quoted_printable_encode($str)
	{
		/**
		 * @link http://www.asciitable.com/
		 * @link http://jlr31130.free.fr/rfc2045.html (paragraphe 6.7)
		 */
		$maxlen = 76;
		
		$str = preg_replace("/\r\n?/", "\n", $str);
		$str = preg_replace("/([\001-\010\013\014\016-\037\075\177-\377])/e",
			'sprintf(\'=%02X\', ord("\\1"));', $str);
		$str = preg_replace("/([\011\040])(?=\n)/e", 'sprintf(\'=%02X\', ord("\\1"));', $str);
		
		$lines = explode("\n", $str);
		$total_lines = count($lines);
		
		for( $i = 0; $i < $total_lines; $i++ )
		{
			if( ($strlen = strlen($lines[$i])) > $maxlen )
			{
				$new_line = '';
				
				do
				{
					$tmp = substr($lines[$i], 0, ($maxlen - 1));
					
					if( ($pos = strrpos($tmp, '=')) && $pos > ($maxlen - 4) )
					{
						$tmp       = substr($tmp, 0, $pos);
						$lines[$i] = '=' . substr($lines[$i], ($pos + 1));
						$strlen    = ($strlen - strlen($tmp));
					}
					else
					{
						$lines[$i] = substr($lines[$i], ($maxlen - 1));
						$strlen    = ($strlen - ($maxlen - 1));
					}
					
					$new_line .= $tmp;
					if( $strlen > 0 )
					{
						$new_line .= "=\n";
						
						if( $strlen <= $maxlen )
						{
							$new_line .= $lines[$i];
							break;
						}
					}
				}
				while( $strlen > 0 );
				
				$lines[$i] = $new_line;
			}
		}
		
		$str = implode("\n", $lines);
		
		return $str;
	}
	
	/**
	 * @param string $value  Contenu de l'ent�te
	 * @param string $name   Nom de l'ent�te correspondant
	 * @param string $token  Type de jeton
	 * 
	 * @access public
	 * @return string
	 */
	function encode_mime_header($value, $name, $token = 'text')
	{
		if( preg_match('/[\x00-\x1F\x7F-\xFF]/', $value) )
		{
			$maxlen = 76;
			$sep = "\r\n\x20";
			
			switch( $token )
			{
				case 'comment':
					$charlist = '\x00-\x1F\x22\x28\x29\x3A\x3D\x3F\x5F\x7F-\xFF';
					break;
				case 'phrase':
					$charlist = '\x00-\x1F\x22-\x29\x2C\x2E\x3A\x40\x5B-\x60\x7B-\xFF';
					break;
				case 'text':
				default:
					$charlist = '\x00-\x1F\x3A\x3D\x3F\x5F\x7F-\xFF';
					break;
			}
			
			/**
			 * Si le nombre d'octets � encoder repr�sente plus de 33% de la cha�ne,
			 * nous utiliserons l'encodage base64 qui garantit une cha�ne encod�e 33%
			 * plus longue que l'originale, sinon, on utilise l'encodage "Q".
			 * La RFC 2047 recommande d'utiliser pour chaque cas l'encodage produisant
			 * le r�sultat le plus court.
			 * 
			 * @see RFC 2045#6.8
			 * @see RFC 2047#4
			 */
			$q = preg_match_all("/[$charlist]/", $value, $matches);
			$strlen   = strlen($value);
			$encoding = (($q / $strlen) < 0.33) ? 'Q' : 'B';
			$template = sprintf('=?%s?%s?%%s?=%s', $this->charset, $encoding, $sep);
			$maxlen   = ($maxlen - strlen($template) + strlen($sep) + 2);// + 2 pour le %s dans le mod�le
			$is_utf8  = (strcasecmp($this->charset, 'UTF-8') == 0);
			$newbody  = '';
			$pos = 0;
			
			while( $pos < $strlen )
			{
				$tmplen = $maxlen;
				if( $newbody == '' )
				{
					$tmplen -= strlen($name . ': ');
					if( $encoding == 'Q' ) $tmplen++;// TODO : � comprendre
				}
				
				if( $encoding == 'Q' )
				{
					$q = preg_match_all("/[$charlist]/", substr($value, $pos, $tmplen), $matches);
					// chacun des octets trouv�s prendra trois fois plus de place dans
					// la cha�ne encod�e. On retranche cette valeur de la longueur du tron�on
					$tmplen -= ($q * 2);
				}
				else
				{
					/**
					 * La longueur de l'encoded-text' doit �tre un multiple de 4
					 * pour ne pas casser l'encodage base64
					 * 
					 * @see RFC 2047#5
					 */
					$tmplen -= ($tmplen % 4);
					$tmplen = floor(($tmplen/4)*3);
				}
				
				if( $is_utf8 )
				{
					/**
					 * Il est interdit de sectionner un caract�re multi-octet.
					 * On teste chaque octet en partant de la fin du tron�on en cours
					 * jusqu'� tomber sur un caract�re ascii ou l'octet de d�but de
					 * s�quence d'un caract�re multi-octets.
					 * On v�rifie alors qu'il y bien $m octets qui suivent (le cas �ch�ant).
					 * Si ce n'est pas le cas, on r�duit la longueur du tron�on.
					 * 
					 * @see RFC 2047#5
					 */
					for( $i = min(($pos + $tmplen), $strlen), $c = 1; $i > $pos; $i--, $c++ )
					{
						$d = ord($value{$i-1});
						
						reset($this->_utf8test);
						for( $m = 1; $m <= 6; $m++ )
						{
							$test = each($this->_utf8test);
							if( ($d & $test[0]) == $test[1] )
							{
								if( $c < $m )
								{
									$tmplen -= $c;
								}
								break 2;
							}
						}
					}
				}
				
				$tmp = substr($value, $pos, $tmplen);
				if( $encoding == 'Q' )
				{
					$tmp = preg_replace("/([$charlist])/e", 'sprintf(\'=%02X\', ord("\\1"));', $tmp);
					$tmp = str_replace(' ', '_', $tmp);
				}
				else
				{
					$tmp = base64_encode($tmp);
				}
				
				$newbody .= sprintf($template, $tmp);
				$pos += $tmplen;
			}
			
			$value = str_replace("\r\n", "\n", rtrim($newbody));
		}
		else if( $token != 'text' )
		{
			if( preg_match('/[^!#$%&\'*+\/0-9=?a-z^_`{|}~-]/', $value) )
			{
				$value = '"'.$value.'"';
			}
		}
		
		return $value;
	}
	
	/**
	 * @param string  $str
	 * @param boolean $is_header
	 * 
	 * @access public
	 * @return string
	 */
	function word_wrap($str, $is_header = true, $maxlen = 78)
	{
		if( isset($this) )
		{
			$maxlen = $this->maxlen;
		}
		
		if( $is_header )
		{
			/**
			 * \n<LWS> mais la fonction mail() ne laisse passer les long ent�tes subject et to 
			 * que si on s�pare avec \r\n<LWS>
			 * 
			 * LWS : Linear-White-Space (espace ou tabulation)
			 * 
			 * @link http://cvs.php.net/cvs.php/php4.fubar/ext/standard/mail.c?login=2
			 * 
			 * espace au lieu de tabulation sinon le sujet notamment ne s'affiche pas correctement 
			 * selon les lecteurs d'emails.
			 */
			
			$str = wordwrap($str, $maxlen, "\n ");
		}
		else if( strlen($str) > $maxlen )
		{
			$lines = explode("\n", $str);
			$str   = '';
			foreach( $lines as $line )
			{
				$str .= wordwrap($line, $maxlen, "\n") . "\n";
			}
		}
		
		return trim($str);
	}
	
	/**
	 * Envoie de l'email
	 * 
	 * @param boolean $do_not_send  true pour retourner l'ent�te et le corps
	 *                              du message au lieu d'envoyer l'email
	 * 
	 * @access public
	 * @return boolean
	 */
	function send($do_not_send = false)
	{
		global $php_errormsg;
		
		//
		// Des erreurs se sont produites
		//
		if( !$this->statut )
		{
			return false;
		}
		
		if( $this->smtp_mode )
		{
			$this->hebergeur = WM_SMTP_MODE;
		}
		else if( $this->sendmail_mode )
		{
			$this->hebergeur = WM_SENDMAIL_MODE;
		}
		
		if( $this->format == 3 && empty($this->uncompiled_altmessage[3]) )
		{
			$this->format = 2;
			$this->uncompiled_message[2] = $this->uncompiled_message[3];
		}
		
		$address = $this->recipients_list();
		$headers = $this->compile_headers();
		$message = $this->compile_message();
		$Rpath   = $this->get_return_path();
		
		/**
		 * On encode le sujet de l'email si n�cessaire.
		 * 
		 * FIX
		 * 
		 * La fonction mail() n'accepte les ent�tes long que si on utilise la s�quence CRLFSP (\r\n )
		 * Or, sur certains syst�mes, il semble que les retours de ligne soient ... doubl�s ...
		 * R�sultat, le corps de l'email commence au saut de ligne en trop (et donc contient une bonne 
		 * partie des ent�tes).
		 * Pour �viter cela, on supprime les s�quences LFSP (\n ) ajout�es par la m�thode word_wrap()
		 * 
		 * @link http://bugs.php.net/bug.php?id=24805
		 */
		if( $this->subject != '' )
		{
			$subject = $this->subject;
			if( $this->fix_bug_mail == -1 )
			{
				$subject = str_replace("\n ", "\r\n ", $this->word_wrap($subject));
			}
		}
		else
		{
			$subject = 'No subject';
		}
		
		if( $do_not_send )
		{
			return $headers . "\n\n" . $message;
		}
		
		//
		// D�tection du safe_mode. S'il est activ�, on ne pourra pas
		// r�gler l'adresse email de retour (return-path) avec le
		// cinqui�me argument.
		// En alternative, utilisation de ini_get() et ini_set() sur
		// l'option sendmail_from de PHP
		//
		$safe_mode     = @ini_get('safe_mode');
		$safe_mode_gid = @ini_get('safe_mode_gid');// Ajout pour free.fr et sa config php exotique
		
		if( $safe_mode || $safe_mode_gid )
		{
			$old_Rpath = @ini_get('sendmail_from');
			@ini_set('sendmail_from', $Rpath);
		}
		
		switch( $this->hebergeur )
		{
			case WM_HOST_OTHER:
				if( strncasecmp(PHP_OS, 'Win', 3) === 0 )
				{
					$address = preg_replace('/\r\n?|\n/', "\r\n", $address);
					$subject = preg_replace('/\r\n?|\n/', "\r\n", $subject);
					$message = preg_replace('/\r\n?|\n/', "\r\n", $message);
					$headers = preg_replace('/\r\n?|\n/', "\r\n", $headers);
				}
				
				if( !$safe_mode && !$safe_mode_gid )
				{
					$result = @mail($address, $subject, $message, $headers, '-f' . $Rpath);
				}
				else
				{
					$result = @mail($address, $subject, $message, $headers);
				}
				break;
			
			case WM_HOST_ONLINE:
				list($sender) = explode('@', $this->sender);
				$result = @email($sender, $address, $subject, $message, $sender, $headers);
				break;
			
			case WM_SMTP_MODE:
				$result = $this->smtpmail($address, $message, $headers, $Rpath);
				break;
			
			case WM_SENDMAIL_MODE:
				$result = $this->sendmail($address, $message, $headers, $Rpath);
				break;
			
			default:
				$this->error('send() :: Aucune fonction d\'envoi n\'est d�finie');
				$result = false;
				break;
		}
		
		if( $safe_mode || $safe_mode_gid )
		{
			@ini_set('sendmail_from', $old_Rpath);
		}
		
		if( !$result && !empty($php_errormsg) && stristr($php_errormsg, ' mail()') )
		{
			$this->error('send() :: ' . strip_tags($php_errormsg));
		}
		
		return $result;
	}
	
	/**
	 * Envoi via la classe smtp
	 * 
	 * @param string $address  Adresses des destinataires
	 * @param string $message  Corps de l'email
	 * @param string $headers  Ent�tes de l'email
	 * @param string $Rpath    Adresse d'envoi (d�finit le return-path)
	 * 
	 * @access private
	 * @return boolean
	 */
	function smtpmail($address, $message, $headers, $Rpath)
	{
		if( !is_resource($this->smtp->connect_id) || !$this->smtp->noop() )
		{
			if( !$this->smtp->connect() )
			{
				$this->error($this->smtp->msg_error);
				return false;
			}
		}
		
		if( !$this->smtp->mail_from($Rpath) )
		{
			$this->error($this->smtp->msg_error);
			return false;
		}
		
		foreach( $address AS $email )
		{
			if( !$this->smtp->rcpt_to($email) )
			{
				$this->error($this->smtp->msg_error);
				return false;
			}
		}
		
		if( !$this->smtp->send($headers, $message) )
		{
			$this->error($this->smtp->msg_error);
			return false;
		}
		
		//
		// Apparamment, les commandes ne sont r�ellement effectu�es qu'apr�s la fermeture proprement 
		// de la connexion au serveur SMTP. On quitte donc la connexion courante si l'option de connexion 
		// persistante n'est pas activ�e.
		//
		if( !$this->persistent_connection )
		{
			$this->smtp->quit();
		}
		
		return true;
	}
	
	/**
	 * Envoi via sendmail
	 * (Je me suis beaucoup inspir� ici de la classe de PEAR concernant l'envoi via sendmail)
	 * 
	 * @param string $address  Adresses des destinataires
	 * @param string $message  Corps de l'email
	 * @param string $headers  Ent�tes de l'email
	 * @param string $Rpath    Adresse d'envoi (d�finit le return-path)
	 * 
	 * @access private
	 * @return boolean
	 */
	function sendmail($address, $message, $headers, $Rpath)
	{
		if( @is_executable($this->sendmail_path) )
		{
			$headers = preg_replace("/\r\n?/", "\n", $headers);
			$message = preg_replace("/\r\n?/", "\n", $message);
			
			//
			// Vu dans les notes d'utilisateur sur php.net :
			// 
			// -t
			// Scan the To:, Cc:, and Bcc: from the message itself
			//
			$this->sendmail_cmd  = ( $this->sendmail_cmd != '' ) ? ' ' . $this->sendmail_cmd : '';
			$this->sendmail_cmd .= ' -t -f' . escapeshellcmd($Rpath);
			
			if( is_array($address) && count($address) > 0 )
			{
				$address = escapeshellcmd(implode(' ', $address));
				$this->sendmail_cmd .= ' -- ' . $address;
			}
			
			$mode = ( stristr(PHP_OS, 'WIN') ) ? 'wb' : 'w';
			$code = 0;
			
			if( !($sm = popen($this->sendmail_path . $this->sendmail_cmd, $mode)) )
			{
				$this->error('sendmail() :: Impossible d\'ex�cuter sendmail');
				return false;
			}
			
			//
			// On envoie les ent�tes 
			//
			fputs($sm, $headers . "\n\n");
			
			//
			// Et maintenant le message 
			//
			fputs($sm, $message . "\n");
			
			$code = pclose($sm) >> 8 & 0xFF;
			
			if( $code != 0 )
			{
				$this->error('sendmail() :: Sendmail a retourn� le code d\'erreur suivant -> ' . $code);
				return false;
			}
		}
		else
		{
			$this->error('sendmail() :: ' . $this->sendmail_path . ' n\'est pas ex�cutable');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Retourne l'adresse d'envoi � utiliser dans l'option -f de sendmail 
	 * ou pour la commande MAIL FROM de SMTP car c'est celle ci qui est utilis�e 
	 * pour forger l'ent�te return-path
	 * Nous utiliserons l'adresse email fournie pour le return-path, si cet ent�te n'est pas vide.
	 * S'il est vide, nous utiliserons l'adresse d'exp�diteur fournie. 
	 * Enfin, si celle ci n'a pas �t� fournie non plus, on utilise la valeur de sendmail_from
	 * 
	 * @access private
	 * @return string
	 */
	function get_return_path()
	{
		if( !empty($this->headers['Return-Path']) )
		{
			$Rpath = trim($this->headers['Return-Path'], '<>');
		}
		else if( !empty($this->sender) )
		{
			$Rpath = $this->sender;
		}
		else
		{
			$Rpath = @ini_get('sendmail_from');
			
			if( empty($Rpath) )
			{
				//
				// Pas moyen d'obtenir une adresse � utiliser.
				// En dernier ressort, nous utilisons une adresse factice
				//
				$Rpath = 'wamailer@localhost';
			}
		}
		
		return $Rpath;
	}
	
	/**
	 * Renvoie la liste des destinataires
	 * 
	 * @access private
	 * @return mixed
	 */
	function recipients_list()
	{
		if( empty($this->headers['To']) && empty($this->headers['Cc']) )
		{
			$this->headers['To'] = 'Undisclosed-recipients:;';
		}
		
		//
		// Sendmail/qmail/[...] se charge d�ja de parser les ent�tes To, Cc et Bcc �ventuels
		// On renvoie un tableau vide
		//
		if( $this->sendmail_mode )
		{
			$address = array();
		}
		
		//
		// Mode smtp, on renvoie les adresses de tous les destinataires et on supprime 
		// l'ent�te Bcc
		//
		else if( $this->smtp_mode )
		{
			$this->headers['Bcc'] = '';
			
			$address = $this->address['To'];
			$address = array_merge($address, $this->address['Cc']);
			$address = array_merge($address, $this->address['Bcc']);
		}
		else
		{
			//
			// FIX
			// 
			// Si la fonction mail() utilise sendmail, elle rajoute automatiquement un ent�te To, 
			// or, nous avons d�ja rajout� un ent�te To (pour pouvoir personnaliser les adresses), 
			// et sendmail va parser les deux ent�te To sans distinction. 
			// R�sultat, les emails vont �tre re�us en double ...
			// 
			// Si un serveur smtp est utilis�, la personnalisation des adresses Cc et Bcc ne fonctionne pas
			//
			if( !isset($this->fix_bug_mail) )
			{
				$this->fix_bug_mail = -1;
				
				if( @ini_get('sendmail_path') != '' )
				{
					$this->fix_bug_mail = 1;
				}
				
				//
				// Certains h�bergeurs d�sactivent la fonction ini_get() (sont cons quand m�me nan ?)
				// Pas grave, on r�cup�re le contenu du phpinfo et on le scan (Bwaahahaa ..)
				//
				else
				{
					ob_start();
					@phpinfo(INFO_CONFIGURATION);
					$phpinfo = strtolower(strip_tags(ob_get_contents()));
					ob_end_clean();
					
					if( !empty($phpinfo) )
					{
						if( !preg_match('/^sendmail_pathno valueno value$/im', $phpinfo) )
						{
							$this->fix_bug_mail = 1;
						}
					}
					else
					{
						//
						// Bon, pas moyen de r�cup�rer la valeur de sendmail_path :/
						// Pour que l'envoi se passe tout de m�me sans probl�me, on supprime l'ent�te To
						// Tant pis pour la personnalisation.
						//
						$this->fix_bug_mail = 0;
					}
				}
			}
			
			//
			// Sendmail/qmail/[...] est utilis�, on renvoie le contenu de l'ent�te To et on le supprime
			//
			if( $this->fix_bug_mail == 1 )
			{
				$address = '';
				
				if( !empty($this->headers['To']) )
				{
					$address = $this->headers['To'];
					$this->headers['To'] = '';
				}
			}
			else
			{
				$address = ( count($this->address['To']) > 0 ) ? implode(', ', $this->address['To']) : '';
				
				//
				// FIX
				// 
				// La personnalisation telle que "name" <user@domaine.com> ne marche pas
				// pour l'ent�tes Cc si on utilise la fonction mail() et qu'un serveur
				// smtp est utilis�. On supprime donc la personnalisation de l'ent�te Cc
				// 
				// Dans le doute, on supprime �galement l'ent�te To.
				//
				if( $this->fix_bug_mail == 0 )
				{
					$this->headers['To'] = '';
				}
				
				if( count($this->address['Cc']) > 0 )
				{
					$this->headers['Cc'] = implode(', ', $this->address['Cc']);
				}
				
				if( count($this->address['Bcc']) > 0 )
				{
					$this->headers['Bcc'] = implode(', ', $this->address['Bcc']);
				}
			}
		}
		
		return $address;
	}
	
	/**
	 * Renvoie les ent�tes correspondant au type demand�
	 * 
	 * @param string $type
	 * 
	 * @access private
	 * @return string
	 */
	function make_content_info($type)
	{
		switch( $type )
		{
			case 'mixed':
				$content_info = "Content-Type: multipart/mixed;\n\tboundary=\"" . $this->boundary['part0'][$this->format] . '"';
				break;
			
			case 'related':
				$content_info = "Content-Type: multipart/related;\n\t";
				
				if( $this->format == 3 )
				{
					$content_info .= "type=\"multipart/alternative\";\n\t";
				}
				
				$content_info .= 'boundary="' . $this->boundary['part1'][$this->format] . '"';
			break;
			
			case 3:
				$content_info = "Content-Type: multipart/alternative;\n\tboundary=\"" . $this->boundary['part2'][$this->format] . '"';
				break;
			
			case 2:
				$content_info  = 'Content-Type: text/html; charset="' . $this->charset . "\"\n";
				$content_info .= 'Content-Transfer-Encoding: ' . $this->encoding; 
				break;
			
			case 1:
				$content_info  = 'Content-Type: text/plain; charset=' . $this->charset . "\n";
				$content_info .= 'Content-Transfer-Encoding: ' . $this->encoding;
				break;
			
			default:
				$this->error('make_content_info() :: Type inconnu');
				break;
		}
		
		return $content_info;
	}
	
	/**
	 * G�n�ration du bloc d'en-t�tes
	 * 
	 * @access private
	 * @return string
	 */
	function compile_headers()
	{
		if( $this->smtp_mode || $this->sendmail_mode )
		{
			$this->headers['Subject'] = $this->subject;
		}
		else
		{
			$this->headers['Subject'] = '';
		}
		
		if( !empty($this->sender) && $this->hebergeur != WM_HOST_ONLINE && empty($this->headers['Return-Path']) )
		{
			$this->set_return_path();
		}
		
		$this->headers['Date']         = date('D, d M Y H:i:s O', time());
		$this->headers['X-Mailer']     = 'Wamailer/' . $this->version . ' (http://phpcodeur.net)';
		$this->headers['X-AntiAbuse']  = 'Sender IP - ' . $this->sender_ip . '/Server Name - <' . $this->server_from . '>';
		$this->headers['MIME-Version'] = '1.0'; 
		$this->headers['Message-ID']   = '<' . md5(microtime() . rand()) . '@' . $this->server_from . '>';
		
		//
		// La rfc2822 conseille de placer certains ent�tes dans un certain ordre
		//
		$header_rank = array('Return-Path', 'Date', 'From', 'Subject', 'X-Sender', 'To', 'Cc', 'Bcc', 'Reply-To');
		
		$headers = '';
		foreach( $header_rank as $name )
		{
			if( empty($this->headers[$name]) )
			{
				continue;
			}
			
			$headers .= $this->word_wrap(sprintf('%s: %s', $name,
				preg_replace('/(?!\x09|\x20)\r?\n/', '', $this->headers[$name]))) . "\n";
		}
		
		foreach( $this->headers as $name => $body )
		{
			if( in_array($name, $header_rank) || $body == '' )
			{
				continue;
			}
			
			$headers .= $this->word_wrap(sprintf('%s: %s', $name,
				preg_replace('/(?!\x09|\x20)\r?\n/', '', $body))) . "\n";
		}
		
		if( empty($this->compiled_message[$this->format]) )
		{
			$this->boundary['part0'][$this->format] = '--=_Part0_' . md5(microtime());
			$this->boundary['part1'][$this->format] = '--=_Part1_' . md5(microtime());
			$this->boundary['part2'][$this->format] = '--=_Part2_' . md5(microtime());
			
			if( $this->extract_auto && $this->format > 1 )
			{
				$offset = count($this->embeddedfile['path']);
				preg_match_all(
					'/<(?:[^>]+)(?:data|src|background)\s*=\s*(["\'])(.+?\.([a-z]+))\\1(?:[^>]*)>/Si',
					$this->uncompiled_message[$this->format], $matches, PREG_SET_ORDER
				);
				
				foreach( $matches as $match )
				{
					$name = basename($match[2]);
					$path = $this->root.$name;
					
					if( !is_readable($path) )
					{
						continue;
					}
					
					$this->embeddedfile['path'][$offset]     = $path;
					$this->embeddedfile['name'][$offset]     = $name;
					$this->embeddedfile['mimetype'][$offset] = $this->mime_type($match[3]);
					$offset++;
					
					$data = str_replace($match[2], "cid:$name", $match[0]);
					$this->uncompiled_message[$this->format] = str_replace($match[0],
						$data, $this->uncompiled_message[$this->format]);
				}
			}
		}
		
		$total_attach   = count($this->attachfile['path']);
		$total_embedded = count($this->embeddedfile['path']);
		
		//
		// Si des fichiers joints sont pr�sents, ou si des fichiers incorpor�s sont 
		// pr�sents et que l'email est au format texte brut
		//
		if( $total_attach > 0 || ( $total_embedded > 0 && $this->format == 1 ) )
		{
			$content_info = $this->make_content_info('mixed');
		}
		
		//
		// On ne peut incorporer des fichiers que dans un email html
		//
		else if( $total_embedded > 0 && $this->format > 1 )
		{
			$content_info = $this->make_content_info('related');
		}
		
		//
		// L'email est au format texte brut ou ne contient pas de fichiers joints ou incorpor�s
		//
		else
		{
			$content_info = $this->make_content_info($this->format);
		}
		
		return $headers . $content_info;
	}
	
	/**
	 * G�n�ration du corps de l'email
	 * 
	 * @access private
	 * @return string
	 */
	function compile_message()
	{
		if( empty($this->compiled_message[$this->format]) )
		{
			$attach_ary   = $this->attachfile;
			$embedded_ary = $this->embeddedfile;
			
			$total_attach   = count($attach_ary['path']);
			$total_embedded = count($embedded_ary['path']);
			
			if( $total_embedded > 0 && $this->format == 1 )
			{
				for( $i = 0; $i < $total_embedded; $i++ )
				{
					$attach_ary['path'][]        = $embedded_ary['path'][$i];
					$attach_ary['name'][]        = $embedded_ary['name'][$i];
					$attach_ary['mimetype'][]    = $embedded_ary['mimetype'][$i];
					$attach_ary['disposition'][] = 'attachment';
					
					$total_attach++;
				}
				
				$total_embedded = 0;
			}
			
			$message = '{WAMAILER_MSG}';
			
			if( $total_embedded > 0 )
			{
				$tmp_msg = $message;
				
				$message  = '--' . $this->boundary['part1'][$this->format] . "\n";
				$message .= $this->make_content_info($this->format);
				$message .= "\n\n";
				$message .= $tmp_msg;
				$message .= "\n";
				
				for( $i = 0; $i < $total_embedded; $i++ )
				{
					$message .= $this->insert_attach(
						$embedded_ary['path'][$i],
						$embedded_ary['name'][$i],
						$embedded_ary['mimetype'][$i],
						'',
						$this->boundary['part1'][$this->format],
						true
					);
				}
				
				$message .= '--' . $this->boundary['part1'][$this->format] . "--\n";
			}
			
			if( $total_attach > 0 )
			{
				$tmp_msg = $message;
				
				if( $total_embedded > 0 )
				{
					$content_info = $this->make_content_info('related');
				}
				else
				{
					$content_info = $this->make_content_info($this->format);
				}
				
				$message  = '--' . $this->boundary['part0'][$this->format] . "\n";
				$message .= $content_info;
				$message .= "\n\n";
				$message .= $tmp_msg;
				$message .= "\n";
				
				for( $i = 0; $i < $total_attach; $i++ )
				{
					$message .= $this->insert_attach(
						$attach_ary['path'][$i],
						$attach_ary['name'][$i],
						$attach_ary['mimetype'][$i],
						$attach_ary['disposition'][$i],
						$this->boundary['part0'][$this->format],
						false
					);
				}
				
				$message .= '--' . $this->boundary['part0'][$this->format] . "--\n";
			}
			
			if( $this->format == 3 || $total_attach > 0 || $total_embedded > 0 )
			{
				$message = "This is a multi-part message in MIME format.\n\n" . $message;
			}
			
			$this->compiled_message[$this->format] = $message;
		}
		
		if( $this->format == 3 )
		{
			$altbody = $this->replace_tags($this->uncompiled_altmessage[$this->format]);
			$body    = $this->replace_tags($this->uncompiled_message[$this->format]);
			
			$message  = '--' . $this->boundary['part2'][$this->format] . "\n";
			$message .= $this->make_content_info(1);
			$message .= "\n\n";
			$message .= $this->make_encoding($this->encoding, $altbody);
			$message .= "\n";
			$message .= '--' . $this->boundary['part2'][$this->format] . "\n";
			$message .= $this->make_content_info(2);
			$message .= "\n\n";
			$message .= $this->make_encoding($this->encoding, $body);
			$message .= "\n";
			$message .= '--' . $this->boundary['part2'][$this->format] . "--\n";
		}
		else
		{
			$message = $this->make_encoding(
				$this->encoding,
				$this->replace_tags($this->uncompiled_message[$this->format])
			);
		}
		
		return str_replace('{WAMAILER_MSG}', $message, $this->compiled_message[$this->format]);
	}
	
	/**
	 * @param string $texte
	 * 
	 * @access private
	 * @return string
	 */
	function replace_tags($texte)
	{
		if( count($this->tags) > 0 )
		{
			$keys = $values = array();
			foreach( $this->tags as $key => $val )
			{
				$keys[]   = '/(?:(%)|(\{))'.$key.'(?(1)%|\})/i';
				$values[] = $val;
			}
			
			$texte = preg_replace($keys, $values, $texte);
		}
		
		return $this->replace_block_tags($texte);
	}
	
	/**
	 * @param string $texte
	 * 
	 * @access private
	 * @return string
	 */
	function replace_block_tags($texte)
	{
		$total_blocks = preg_match_all(
			"/<!-- start_block ([[:alnum:]_-]+) -->(.*?)<!-- end_block \\1 -->([\r\n]+)/is",
			$texte,
			$matches
		);
		
		for( $i = 0; $i < $total_blocks; $i++ )
		{
			$name = $matches[1][$i];
			$tmp = '';
			
			if( isset($this->block_tags[$name]) && count($this->block_tags[$name]) )
			{
				$keys = $values = array();
				foreach( $this->block_tags[$name] as $key => $val )
				{
					$keys[]   = '/(?:(%)|(\{))' . $name . '\.' . $key . '(?(1)%|\})/i';
					$values[] = $val;
				}
				
				$tmp = preg_replace($keys, $values, trim($matches[2][$i])) . $matches[3][$i];
			}
			
			$texte = str_replace($matches[0][$i], $tmp, $texte);
		}
		
		return $texte;
	}
	
	/**
	 * @param string  $path         Chemin vers le fichier
	 * @param string  $filename     Nom du fichier
	 * @param string  $mime_type    Type de m�dia du fichier
	 * @param string  $disposition  Disposition
	 * @param string  $boundary     Fronti�re � utiliser
	 * @param boolean $embedded     Si fichier incorpor�, true
	 * 
	 * @access private
	 * @return string
	 */
	function insert_attach($path, $filename, $mime_type, $disposition, $boundary, $embedded)
	{
		if( $mime_type == '' )
		{
			$extension = 'wa';
			if( $dot_pos = strrpos($filename, '.') )
			{
				$extension = strtolower(substr($filename, ($dot_pos + 1)));
			}
			
			$mime_type = $this->mime_type($extension);
		}
		
		$attach  = '--' . $boundary . "\n";
		$attach .= "Content-Type: $mime_type;\n\tname=\"$filename\"\n";
		$attach .= "Content-Transfer-Encoding: base64\n";
		
		if( $embedded )
		{
			$cid = md5(microtime()) . '@wamailer';
			
			$attach .= 'Content-ID: <' . $cid . '>' . "\n\n";
			
			$this->uncompiled_message[$this->format] = preg_replace(
				'/<([^>]+=\s*)(["\'])cid:' . preg_quote($filename, '/') . '\\2([^>]*)>/S',
				'<\\1\\2cid:' . $cid . '\\2\\3>',
				$this->uncompiled_message[$this->format]
			);
		}
		else
		{
			$attach .= 'Content-Disposition: ' . $disposition . ";\n\tfilename=\"" . $filename . "\"\n\n";
		}
		
		$attach .= $this->make_encoding('base64', $this->loadfile($path, true)) . "\n";
		
		return $attach;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_all()
	{
		$this->clear_from();
		$this->clear_address();
		$this->clear_subject();
		$this->clear_message();
		$this->clear_attach();
		
		$this->format    = 1;
		$this->headers   = array();
		$this->msg_error = '';
		$this->statut    = true;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_from()
	{
		$this->sender    = '';
		$this->msg_error = '';
		$this->statut    = true;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_address()
	{
		$this->address   = array('To' => array(), 'Cc' => array(), 'Bcc' => array());
		$this->msg_error = '';
		$this->statut    = true;
		
		unset($this->headers['To'], $this->headers['Cc'], $this->headers['Bcc']);
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_subject()
	{
		$this->subject   = '';
		$this->msg_error = '';
		$this->statut    = true;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_message()
	{
		$this->uncompiled_message    = array();
		$this->uncompiled_altmessage = array();
		$this->compiled_message      = array();
		$this->tags                  = array();
		$this->block_tags            = array();
		$this->boundary              = array('part0' => array(), 'part1' => array(), 'part2' => array());
		$this->msg_error             = '';
		$this->statut                = true;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_attach()
	{
		$this->attachfile   = array('path' => array(), 'name' => array(), 'mimetype' => array(), 'disposition' => array());
		$this->embeddedfile = array('path' => array(), 'name' => array(), 'mimetype' => array());
		$this->msg_error    = '';
		$this->statut       = true;
	}
	
	/**
	 * @param string $msg_error  Le message d'erreur � afficher si mode debug
	 * 
	 * @access private
	 * @return void
	 */
	function error($msg_error)
	{
		if( $this->debug )
		{
			exit($msg_error);
		}
		
		if( $this->msg_error == '' )
		{
			$this->msg_error = $msg_error;
		}
		
		$this->statut = false;
	}
}// fin de la classe

}
?>