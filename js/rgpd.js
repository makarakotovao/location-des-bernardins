const settings = {
  domain: '',
  path: '/',
  cookieName: 'cookie-agreed-categories',
  lifetime: 100
}

const cookiesTracked = {
  functional: [],
  statistics: [
    '_ga*',
    'gid',
    '_dc_gtm_*',
    '__utm*'
  ],
  tiers: [
    'player',
    'vuid',
    'continuous_play_v3'
  ]
}

// Set cookie when consent is givent for some categories
function setAcceptedCategories(categories) {
  let date = new Date();
  const domain = settings.domain ? settings.domain : '';
  const path = settings.path ? settings.path : '/';
  const cookieName = settings.cookieName ? settings.cookieName : 'cookie-agreed-categories';
  const categoriesString = JSON.stringify(categories);
  const lifetime = settings.lifetime ? settings.lifetime : 100;
  date.setDate(date.getDate() + lifetime);
  $.cookie(cookieName, categoriesString, { expires: date, path: path, domain: domain });

  if (categories.includes('statistics')) {
    gaSSDSLoad();
  }

  // Close banner and modal
  $('.manage-cookies-popup').hide();
  $('.eu-cookie-compliance-banner').hide();
  $('#globalWrapper').removeClass('zone-blur');
  $(document).trigger('changeCookieConsentPreferences', [categories]);

}

// Check if a consent for a category has already been given
function hasAgreed(category) {
  const cookieName = settings.cookieName ? settings.cookieName : 'cookie-agreed-categories';
  const categoriesString = $.cookie(cookieName);
  if (!categoriesString) {
    return false;
  }
  const categories = JSON.parse(categoriesString);
  if (category === undefined) {
    return true;
  }
  return categories.includes(category);
}

function cookieMatches(cookieName, pattern) {
  if (cookieName === pattern) {
    return true;
  }
  if (pattern.indexOf('*') < 0) {
    return false;
  }
  try {
    const regexp = new RegExp('^' + pattern.replace(/\./g, '\\.').replace(/\*/g, '.+') + '$', 'g');
    return regexp.test(cookieName);
  }
  catch (err) {
    return false;
  }
}

function isAllowed(cookieName) {
  const name = settings.cookieName ? settings.cookieName : 'cookie-agreed-categories';
  if (cookieName === name) {
    return true;
  }
  const categoriesString = $.cookie(name);
  if (!categoriesString) {
    return false;
  }
  const categories = JSON.parse(categoriesString);
  if (!categories) {
    return false;
  }
  for (const category of categories) {
    const cookiesList = cookiesTracked[category];
    for (const cookie of cookiesList) {
      if (cookieMatches(cookieName, cookie)) {
        return true;
      }
    }
  }
  return false;
}

function blockCookies() {
  return true;
  // Load all cookies.
  const cookies = $.cookie();

  // Check each cookie and try to remove it if it's not allowed.
  for (const i in cookies) {
    let remove = true;
    let hostname = window.location.hostname;
    let cookieRemoved = false;
    let index = 0;

    remove = !isAllowed(i);

    // Remove the cookie if it's not allowed.
    if (remove) {
      while (!cookieRemoved && hostname !== '') {
        // Attempt to remove.
        cookieRemoved = $.removeCookie(i, { domain: '.' + hostname, path: '/' });
        if (!cookieRemoved) {
          cookieRemoved = $.removeCookie(i, { domain: hostname, path: '/' });
        }

        index = hostname.indexOf('.');

        // We can be on a sub-domain, so keep checking the main domain as well.
        hostname = (index === -1) ? '' : hostname.substring(index + 1);
      }
    }
  }
}

$(document).ready(function() {
  'use strict';
  if (!hasAgreed("functional")) {
    $('#globalWrapper').addClass('zone-blur');
    const height = $('#sliding-popup').outerHeight();
    $('#sliding-popup').show().css({ bottom: -1 * height });
    $('#sliding-popup').animate({bottom: 0}, 1000, null);
  } else {
    $('#sliding-popup').hide();
  }

  setInterval(blockCookies, 5000);

  // Toggle Manage Preferences modal
  const onClickManagePreferences = () => {
    $('.manage-cookies-popup').toggle();
    $('.eu-cookie-compliance-banner').toggle();
  }

  $('.euccp-manage-button').click(onClickManagePreferences);
  $('.manage-cookies-popup-close-button').click(onClickManagePreferences);

  // Save preferences
  $('.eu-cookie-compliance-save-preferences-button').click(() => {
    const categories = $("#eu-cookie-compliance-categories input:checkbox:checked").map(function () {
      return $(this).val();
    }).get();
    setAcceptedCategories(categories);
  });

  // Save all
  $('.agree-button').click(() => {
    const categories = $("#eu-cookie-compliance-categories input:checkbox").map(function () {
      return $(this).val();
    }).get();
    setAcceptedCategories(categories);
  });

  // Decline all
  $('.euccp-disable-all-button').click(() => {
    setAcceptedCategories([]);
  });

  $('.euccp-allow-all-categories-button').click(() => {
    const managePopup = $('.manage-cookies-popup');
    const isChecked = $('.euccp-allow-all-categories-button').prop('checked');
    managePopup.find('#eu-cookie-compliance-categories .eu-cookie-compliance-category').each(function() {
      $(this).find('input[type=checkbox]:not(:disabled)').prop('checked', isChecked);
    });
  });

});

// Listen event cookie consent is changed
$(document).on('changeCookieConsentPreferences', () => {
  blockCookies();
});
