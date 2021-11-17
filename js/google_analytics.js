/*************** GOOGLE ANALYTICS ***********/

/* ANALYTICS */

var gaSSDSLoad = () => {
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)
  },i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  // ga('create', 'UA-2780956-5', 'location-des-bernardins.fr');
  ga('create', 'UA-2780956-5', 'auto');
  ga('require', 'displayfeatures');
  ga('send', 'pageview');
}

//load after page onload
window.onload = function () {
  "use strict";
  // Block if consent not given
  if (hasAgreed !== undefined) {
    if (hasAgreed('statistics')) {
      gaSSDSLoad();
    }
  }
};


