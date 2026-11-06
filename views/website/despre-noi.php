<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\SiteSeo;

/* ==========================================
   PAGE SEO
========================================== */

$this->title = 'Despre noi | Anna Accesorii Nuntă';

$this->params['metaDescription'] =
    'Descoperă Anna Accesorii Nuntă și gama de accesorii '
    . 'pentru nuntă, cununie și momente speciale.';
$this->params['canonicalUrl'] = SiteSeo::url('/despre-noi');

/* ==========================================
   CONTACT INFORMATION
   Confirm details before publication
========================================== */

$phone = '+373 69 620 677';
$phoneHref = '+37369620677';

$instagram = '@anna_accesorii_nunta';
$instagramUrl = 'https://www.instagram.com/anna_accesorii_nunta/';

$location = 'Chișinău, Moldova';

?>

<?php /* ==========================================
   ABOUT PAGE - START
========================================== */ ?>

<main class="about-page">

    <?php /* ==========================================
       ABOUT INTRODUCTION
    ========================================== */ ?>

    <section class="about-intro" aria-labelledby="about-title">

        <div class="about-container">

            <div class="about-section-heading">
                <h1 id="about-title">Despre atelier</h1>
            </div>

            <div class="about-intro-grid">

                <?php /* SHOP PHOTOGRAPH */ ?>

                <div class="about-image-card">

                    <img
                        src="<?= Url::to('@web/images/anna-shop-1.jpg') ?>"
                        alt="Accesorii pentru nuntă și cununie expuse la Anna Accesorii Nuntă"
                        width="1600"
                        height="1200"
                        loading="eager"
                        fetchpriority="high">

                </div>

                <?php /* ABOUT TEXT */ ?>

                <div class="about-intro-content">

                    <h2>Detalii pentru momente speciale</h2>

                    <p>
                        La Anna Accesorii Nuntă găsești accesorii
                        pentru nuntă și cununie, de la articole
                        pentru ceremonie până la detalii decorative
                        pentru eveniment.
                    </p>

                    <p>
                        În colecțiile noastre poți descoperi
                        lumânări, cocarde, buchete și alte
                        accesorii potrivite pentru aceste ocazii.
                    </p>

                    <?php /* PRODUCT CATEGORIES */ ?>

                    <div class="about-services">

                        <h3>Ce găsești la noi</h3>

                        <ul>
                            <li>Accesorii pentru nuntă și cununie</li>
                            <li>Lumânări și cocarde</li>
                            <li>Buchete și aranjamente decorative</li>
                            <li>Pahare și alte accesorii pentru ceremonie</li>
                        </ul>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <?php /* ==========================================
       HOW WE WORK
    ========================================== */ ?>

    <section class="about-process" aria-labelledby="about-process-title">

        <div class="about-container">

            <div class="about-process-grid">

                <?php /* PROCESS CONTENT */ ?>

                <div class="about-process-content">

                    <h2 id="about-process-title">Cum lucrăm</h2>

                    <p class="about-process-intro">
                        Fiecare eveniment are propriile detalii.
                        Alegerea accesoriilor începe cu produsele
                        care se potrivesc stilului și preferințelor tale.
                    </p>

                    <?php /* PROCESS STEPS */ ?>

                    <div class="about-steps">

                        <div class="about-step">
                            <span class="about-step-number">01</span>

                            <div>
                                <h3>Descoperi produsele</h3>
                                <p>
                                    Explorezi colecțiile și alegi
                                    accesoriile care îți plac.
                                </p>
                            </div>
                        </div>

                        <div class="about-step">
                            <span class="about-step-number">02</span>

                            <div>
                                <h3>Discutăm detaliile</h3>
                                <p>
                                    Ne contactezi pentru informații
                                    despre produse și disponibilitate.
                                </p>
                            </div>
                        </div>

                        <div class="about-step">
                            <span class="about-step-number">03</span>

                            <div>
                                <h3>Confirmăm comanda</h3>
                                <p>
                                    Stabilim împreună produsele alese
                                    și detaliile necesare comenzii.
                                </p>
                            </div>
                        </div>

                    </div>

                </div>

                <?php /* SECOND SHOP PHOTOGRAPH */ ?>

                <div class="about-image-card about-process-image">

                    <img
                        src="<?= Url::to('@web/images/anna-shop-2.jpg') ?>"
                        alt="Colecție de accesorii și articole pentru cununie"
                        loading="lazy">

                </div>

            </div>

        </div>

    </section>

    <?php /* ==========================================
       CONTACT SECTION
    ========================================== */ ?>

    <section class="about-contact" aria-labelledby="about-contact-title">

        <div class="about-container">

            <div class="about-contact-panel">

                <?php /* CONTACT HEADING */ ?>

                <div class="about-contact-heading">

                    <h2 id="about-contact-title">Contactează-ne</h2>

                    <p>
                        Ai întrebări despre produse?
                        Ne poți contacta pentru mai multe detalii.
                    </p>

                </div>

                <?php /* CONTACT CARDS */ ?>

                <div class="about-contact-grid">

                    <?php /* PHONE */ ?>

                    <div class="about-contact-card">

                        <span class="about-contact-label">Telefon</span>

                        <a href="<?= Html::encode('tel:' . $phoneHref) ?>">
                            <?= Html::encode($phone) ?>
                        </a>

                    </div>

                    <?php /* WHATSAPP */ ?>

                    <div class="about-contact-card">

                        <span class="about-contact-label">WhatsApp</span>

                        <a
                            href="<?= Html::encode('https://wa.me/' . ltrim($phoneHref, '+')) ?>"
                            target="_blank"
                            rel="noopener noreferrer">
                            Trimite mesaj
                        </a>

                    </div>

                    <?php /* INSTAGRAM */ ?>

                    <div class="about-contact-card">

                        <span class="about-contact-label">Instagram</span>

                        <a
                            href="<?= Html::encode($instagramUrl) ?>"
                            target="_blank"
                            rel="noopener noreferrer">
                            <?= Html::encode($instagram) ?>
                        </a>

                    </div>

                    <?php /* LOCATION */ ?>

                    <div class="about-contact-card">

                        <span class="about-contact-label">Locație</span>

                        <span class="about-contact-value">
                            <?= Html::encode($location) ?>
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </section>

</main>

<?php /* ==========================================
   ABOUT PAGE - END
========================================== */ ?>
