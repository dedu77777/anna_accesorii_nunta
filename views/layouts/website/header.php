<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */

// SHOPPING CART COUNTER FROM MYSQL
$cartCount = 0;

$session = Yii::$app->session;
$session->open();

$cart = \app\models\Cart::findOne([
    'session_id' => $session->getId(),
]);

if ($cart !== null) {
    $cartCount = (int) \app\models\CartItem::find()
        ->where(['cart_id' => $cart->id])
        ->sum('quantity');
}

$currentRoute = Yii::$app->controller->route;

$homeUrl = Url::to(['/website/home']);
$productsUrl = Url::to(['/website/produse']);
$aboutUrl = Url::to(['/website/despre-noi']);
$cartUrl = Url::to(['/website/cos']);

$search = (string) Yii::$app->request->get('q', '');
?>

<header class="anna-header">

    <div class="anna-header-container">

        <!-- LOGO -->
        <?= Html::a(
            'ANNA ACCESORII NUNTĂ',
            $homeUrl,
            ['class' => 'anna-header-logo']
        ) ?>

        <!-- DESKTOP NAVIGATION -->
        <nav class="anna-header-nav"
            aria-label="Navigare principală">

            <?= Html::a(
                'Acasă',
                $homeUrl,
                [
                    'class' => $currentRoute === 'website/home'
                        ? 'active'
                        : ''
                ]
            ) ?>

            <?= Html::a(
                'Colecții',
                $productsUrl,
                [
                    'class' => str_starts_with(
                        $currentRoute,
                        'website/produse'
                    ) ? 'active' : ''
                ]
            ) ?>

            <?= Html::a(
                'Despre',
                $aboutUrl,
                [
                    'class' => $currentRoute === 'website/about'
                        ? 'active'
                        : ''
                ]
            ) ?>

        </nav>

        <!-- HEADER ACTIONS -->
        <div class="anna-header-actions">

            <!-- SHOPPING CART -->
            <a
                href="<?= Html::encode($cartUrl) ?>"
                class="anna-cart-icon"
                aria-label="Coșul meu, <?= (int) $cartCount ?> produse">

                <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true">
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="20" cy="21" r="1" />
                    <path d="M1 1h4l2.5 13h12l2.5-9H6" />
                </svg>

                <?php if ($cartCount > 0): ?>
                    <span class="anna-cart-count">
                        <?= (int) $cartCount ?>
                    </span>
                <?php endif; ?>

            </a>

            <!-- MOBILE MENU BUTTON -->
            <button
                type="button"
                class="anna-menu-toggle"
                aria-label="Deschide meniul"
                aria-expanded="false"
                aria-controls="anna-mobile-nav">
                <span></span>
                <span></span>
                <span></span>
            </button>

        </div>

    </div>

    <!-- MOBILE NAVIGATION -->
    <nav
        id="anna-mobile-nav"
        class="anna-mobile-nav"
        aria-label="Navigare mobilă"
        hidden>
        <?= Html::a('Acasă', $homeUrl) ?>
        <?= Html::a('Colecții', $productsUrl) ?>
        <?= Html::a('Despre', $aboutUrl) ?>
    </nav>

</header>

<?php

$this->registerJs(
    <<<'JS'
const menuButton = document.querySelector('.anna-menu-toggle');
const mobileNav = document.querySelector('.anna-mobile-nav');

if (menuButton && mobileNav) {
    menuButton.addEventListener('click', () => {
        const isOpen =
            menuButton.getAttribute('aria-expanded') === 'true';

        menuButton.setAttribute(
            'aria-expanded',
            String(!isOpen)
        );

        menuButton.setAttribute(
            'aria-label',
            isOpen ? 'Deschide meniul' : 'Închide meniul'
        );

        mobileNav.hidden = isOpen;
    });
}
JS
);

?>