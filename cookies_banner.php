<div id="sliding-popup" class="sliding-popup-bottom">
  <?php $classes = array(
    'eu-cookie-compliance-banner',
    'eu-cookie-compliance-banner-info',
    'eu-cookie-compliance-banner--categories',
  ); ?>
  <div class="<?php print implode(' ', $classes); ?>">
    <div class="popup-content info">
      <div id="popup-text">
        <p>Cher visiteur,</p><br>
        <p>Le site collegedesbernardins.fr utilise des cookies afin d'améliorer votre expérience de navigation et établir des statistiques d'utilisation. Vous pouvez accepter les cookies ou gérer vos préférences directement sur notre site.</p>
      </div>

      <div id="popup-buttons" class="eu-cookie-compliance-has-categories">
        <button type="button" class="euccp-default-button eu-cookie-compliance-default-button euccp-disable-all-button">Tout refuser</button>
        <button type="button" class="euccp-default-button eu-cookie-compliance-default-button euccp-manage-button">Gérer les préférences</button>
        <button type="button" class="euccp-agree-button agree-button eu-cookie-compliance-default-button">Tout accepter</button>
      </div>
    </div>
  </div>
  <div class="manage-cookies-popup">
    <div>
      <p class="manage-cookies-title">Gestion des cookies</p>
      <button class="manage-cookies-popup-close-button">×</button>
    </div>
    <hr>
    <div class='euccp-manage-cookies-info'>
      Pour information, les cookies techniques sont nécessaires au bon fonctionnement de notre site. Ils vous permettent d'utiliser les principales fonctionnalités du site. Ils sont donc indispensables et ne peuvent donc pas être désactivés.
    </div>
    <hr>
    <div class="euccp-allow-all-category">
      <div>
        <label for="allow-all-categories">Accepter tous les cookies</label>
        <label class="switch">
          <input type="checkbox" name="allow-all-categories" class="euccp-allow-all-categories-button" id="allow-all-categories">
          <span class="slider round"></span>
        </label>
      </div>
    </div>
    <hr>
    <div id="eu-cookie-compliance-categories" class="eu-cookie-compliance-categories">
      <div class="eu-cookie-compliance-category">
        <div>
          <label for="cookie-category-functional">Cookies techniques</label>
          <label class="switch">
            <input type="checkbox" name="cookie-categories" id="cookie-category-functional" value="functional" checked="" disabled="">
            <span class="slider round"></span>
          </label>
        </div>
        <div class="eu-cookie-compliance-category-description">
          Les cookies techniques sont les cookies nécessaires au bon fonctionnement du site et qui vous permettent d'en apprécier les principales fonctionnalités.
        </div>
      </div>
      <hr>
      <div class="eu-cookie-compliance-category">
        <div>
          <label for="cookie-category-tiers">Cookies tiers</label>
          <label class="switch">
            <input type="checkbox" name="cookie-categories" id="cookie-category-tiers" value="tiers">
            <span class="slider round"></span>
          </label>
        </div>
        <div class="eu-cookie-compliance-category-description">
          Les services de partage de vidéo permettent d'enrichir le site de contenu multimédia. Vous ne pourrez pas visionner de vidéos ou écouter les audios sur notre site si vous les désactivez : YouTube, Soundcloud, Vimeo, Dailymotion.
        </div>
      </div>
      <hr>
      <div class="eu-cookie-compliance-category">
        <div>
          <label for="cookie-category-statistics">Cookies de suivi statistique</label>
          <label class="switch">
            <input type="checkbox" name="cookie-categories" id="cookie-category-statistics" value="statistics">
            <span class="slider round"></span>
          </label>
        </div>
        <div class="eu-cookie-compliance-category-description">
          Les cookies de mesure d'audience permettent de générer des statistiques de fréquentation utiles à l'amélioration du site : Analytics, Hotjar, Dolist, Facebook, etc.
        </div>
      </div>
      <hr>
      <div class="eu-cookie-compliance-categories-buttons">
        <button type="button" class="euccp-agree-button eu-cookie-compliance-save-preferences-button">Enregistrer mes préférences</button>
      </div>
    </div>
  </div>
</div>