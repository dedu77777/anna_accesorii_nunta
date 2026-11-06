
<?php

use yii\helpers\Html;

$instagramUrl = 'https://www.instagram.com/anna_accesorii_nunta';

$phone = '+37369620677';
$whatsappUrl = 'https://wa.me/37369620677';
?>

<footer class="anna-footer">
    <div class="anna-footer-container">

        <div class="anna-footer-grid">

            <!-- BRAND -->
            <div class="anna-footer-brand">
                <h2>ANNA ACCESORII NUNTĂ</h2>

                <p class="anna-footer-description">
                    Detalii elegante pentru momente speciale.
                </p>

                <p class="anna-footer-location">
                    <span aria-hidden="true">⌖</span>
                    Chișinău, Moldova
                </p>
            </div>

            <!-- NAVIGATION -->
            <div class="anna-footer-column">
                <h3>NAVIGARE</h3>

                <nav aria-label="Navigare footer">
                    <?= Html::a('Acasă', ['/website/home']) ?>

                    <?= Html::a('Colecții', ['/website/produse']) ?>

                    <?= Html::a('Despre', ['/website/despre-noi']) ?>
                </nav>
            </div>

            <!-- CONTACT -->
            <div class="anna-footer-column">
                <h3>CONTACT DIRECT</h3>

                <div class="anna-footer-contact">
                    <a href="tel:<?= Html::encode($phone) ?>">
                        <span aria-hidden="true">☎</span>
                        +373 69 620 677
                    </a>

                    <a href="<?= Html::encode($whatsappUrl) ?>"
                       target="_blank"
                       rel="noopener noreferrer">
                        <span aria-hidden="true">◉</span>
                        WhatsApp
                    </a>

                    <a href="<?= Html::encode($instagramUrl) ?>"
                       target="_blank"
                       rel="noopener noreferrer">
                        <span aria-hidden="true">◎</span>
                        @anna_accesorii_nunta
                    </a>
                </div>
            </div>

        </div>

        <div class="anna-footer-bottom">
            <p>
                &copy; <?= date('Y') ?>
                ANNA ACCESORII NUNTĂ.
                Toate drepturile rezervate.
            </p>
        </div>

    </div>
</footer>
