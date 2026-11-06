// WEBSITE MOTION
(function () {
    'use strict';

    var root = document.documentElement;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var main = document.getElementById('main');
    var splashActive = root.classList.contains('anna-splash-active');

    function startPageEntrance() {
        if (reduceMotion || !main) {
            return;
        }
        var hero = main.querySelector('.home-hero');
        var intro = main.querySelector('.about-intro');
        if (hero) {
            hero.classList.add('anna-hero-ready');
        } else if (intro) {
            intro.classList.add('anna-intro-ready');
        }
    }

    if (splashActive) {
        var splash = document.querySelector('.anna-splash');
        var finished = false;

        function finishSplash() {
            if (finished) {
                return;
            }
            finished = true;
            root.classList.remove('anna-splash-active');
            if (splash) {
                splash.remove();
            }
            startPageEntrance();
        }

        if (splash) {
            splash.addEventListener('animationend', function (event) {
                if (event.target === splash && event.animationName === 'anna-curtain') {
                    finishSplash();
                }
            });
            if (window.getComputedStyle(splash).visibility === 'hidden') {
                finishSplash();
            }
        }

        // Failsafe if the animation event is unavailable or missed.
        var elapsed = window.annaSplashStartedAt ? performance.now() - window.annaSplashStartedAt : 0;
        window.setTimeout(finishSplash, Math.max(0, 2400 - elapsed));
    } else {
        startPageEntrance();
    }

    if (reduceMotion || !main || !('IntersectionObserver' in window)) {
        return;
    }

    var sections = main.querySelectorAll('.home-categories, .home-products, .home-faq, .about-process, .about-contact');
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('anna-revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    sections.forEach(function (section) {
        observer.observe(section);
    });
}());
