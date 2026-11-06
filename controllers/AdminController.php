<?php

namespace app\controllers;

use app\components\AdminImageStorage;
use app\components\AdminLoginLimiter;
use app\components\SiteRefresh;
use app\models\AdminLoginForm;
use app\models\Category;
use app\models\CategoryForm;
use app\models\CartItem;
use app\models\OrderItem;
use app\models\ProductForm;
use app\models\ProductImages;
use app\models\Products;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;

class AdminController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => [
                    'dashboard-anna-accesorii', 'publish-site-refresh', 'products-anna-accesorii',
                    'add-product-anna-accesorii', 'edit-product-anna-accesorii',
                    'delete-product-anna-accesorii', 'categories-anna-accesorii',
                    'move-product-anna-accesorii', 'move-category-anna-accesorii',
                    'add-category-anna-accesorii', 'edit-category-anna-accesorii',
                    'delete-category-anna-accesorii',
                ],
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'admin-anna-accesorii' => ['GET', 'POST'],
                    'dashboard-anna-accesorii' => ['GET'],
                    'publish-site-refresh' => ['POST'],
                    'logout' => ['POST'],
                    'products-anna-accesorii' => ['GET'],
                    'add-product-anna-accesorii' => ['GET', 'POST'],
                    'edit-product-anna-accesorii' => ['GET', 'POST'],
                    'delete-product-anna-accesorii' => ['POST'],
                    'move-product-anna-accesorii' => ['POST'],
                    'categories-anna-accesorii' => ['GET'],
                    'add-category-anna-accesorii' => ['GET', 'POST'],
                    'edit-category-anna-accesorii' => ['GET', 'POST'],
                    'delete-category-anna-accesorii' => ['POST'],
                    'move-category-anna-accesorii' => ['POST'],
                ],
            ],
        ];
    }

    public function actionAdminAnnaAccesorii()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/dashboard-anna-accesorii']);
        }
        $model = new AdminLoginForm();
        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $valid = AdminLoginLimiter::attempt($model);
                if ($valid === null) {
                    Yii::$app->response->statusCode = 429;
                    $model->addError('password', 'Prea multe încercări. Încearcă mai târziu.');
                } elseif ($valid && $model->login()) {
                    return $this->redirect(['/admin/dashboard-anna-accesorii']);
                }
            }
        }
        return $this->render('login', ['model' => $model]);
    }

    public function actionDashboardAnnaAccesorii()
    {
        $this->layout = 'admin';
        return $this->render('dashboard-anna-accesorii', [
            'products' => Products::find()->with('images')->orderBy(['id' => SORT_DESC])->limit(5)->all(),
            'categories' => Category::find()->orderBy(['title' => SORT_ASC])->limit(5)->all(),
            'categoryNames' => Category::find()->select('title')->indexBy('id')->column(),
        ]);
    }

    public function actionPublishSiteRefresh()
    {
        try {
            SiteRefresh::publish();
            Yii::$app->session->setFlash('success', 'Paginile de produse deschise se vor actualiza automat în câteva secunde.');
        } catch (\Throwable $error) {
            Yii::error($error, __METHOD__);
            Yii::$app->session->setFlash('error', 'Site-ul nu a putut fi actualizat. Încearcă din nou.');
        }
        return $this->redirect(['/admin/dashboard-anna-accesorii']);
    }

    public function actionProductsAnnaAccesorii()
    {
        $this->layout = 'admin';
        $query = Products::find()->with('images')->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
        $pages = new Pagination(['totalCount' => (clone $query)->count(), 'pageSize' => 20]);
        return $this->render('products', [
            'products' => $query->offset($pages->offset)->limit($pages->limit)->all(),
            'categoryNames' => Category::find()->select('title')->indexBy('id')->column(),
            'pages' => $pages,
        ]);
    }

    public function actionAddProductAnnaAccesorii()
    {
        $this->layout = 'admin';
        $form = new ProductForm();
        if ($this->loadProductForm($form) && $form->validate()
            && $this->saveProduct(new Products(), $form)) {
            Yii::$app->session->setFlash('success', 'Produsul a fost adăugat.');
            return $this->redirect(['/admin/products-anna-accesorii']);
        }
        return $this->renderProductForm($form, new Products());
    }

    public function actionMoveProductAnnaAccesorii(int $id)
    {
        $this->moveRecord(Products::class, $id, 'products');
        return $this->redirectToListing('products-anna-accesorii');
    }

    public function actionEditProductAnnaAccesorii(int $id)
    {
        $this->layout = 'admin';
        $product = $this->findProduct($id);
        $form = ProductForm::fromProduct($product);
        if ($this->loadProductForm($form) && $form->validate()
            && $this->saveProduct($product, $form)) {
            return $this->redirect(['/admin/products-anna-accesorii']);
        }
        return $this->renderProductForm($form, $product);
    }

    public function actionDeleteProductAnnaAccesorii(int $id)
    {
        $product = $this->findProduct($id);
        if (OrderItem::find()->where(['product_id' => $id])->exists()
            || CartItem::find()->where(['product_id' => $id])->exists()) {
            Yii::$app->session->setFlash('error', 'Produsul este folosit într-o comandă sau într-un coș și nu poate fi șters.');
            return $this->redirect(['/admin/products-anna-accesorii']);
        }
        $title = $product->title;
        $paths = array_unique(array_merge(
            [$product->image],
            array_map(static fn (ProductImages $image): string => $image->image, $product->images)
        ));
        try {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                ProductImages::deleteAll(['product_id' => $id]);
                if (!$product->delete()) {
                    throw new \RuntimeException('Produsul nu a putut fi șters.');
                }
                $transaction->commit();
            } catch (\Throwable $error) {
                $transaction->rollBack();
                throw $error;
            }
            try {
                $this->removeUnusedProductFiles($paths);
            } catch (\Throwable $error) {
                Yii::error($error, __METHOD__);
            }
            Yii::$app->session->setFlash('success', $title . ' a fost șters.');
        } catch (\Throwable $error) {
            Yii::error($error, __METHOD__);
            Yii::$app->session->setFlash('error', 'Produsul nu a putut fi șters.');
        }
        return $this->redirect(['/admin/products-anna-accesorii']);
    }

    public function actionCategoriesAnnaAccesorii()
    {
        $this->layout = 'admin';
        $query = Category::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
        $pages = new Pagination(['totalCount' => (clone $query)->count(), 'pageSize' => 20]);
        return $this->render('categories', [
            'categories' => $query->offset($pages->offset)->limit($pages->limit)->all(),
            'pages' => $pages,
        ]);
    }

    public function actionAddCategoryAnnaAccesorii()
    {
        $this->layout = 'admin';
        $form = new CategoryForm();
        if ($this->loadCategoryForm($form) && $form->validate()
            && $this->saveCategory(new Category(), $form)) {
            Yii::$app->session->setFlash('success', 'Categoria a fost adăugată.');
            return $this->redirect(['/admin/categories-anna-accesorii']);
        }
        return $this->render('category-form', ['form' => $form, 'category' => new Category()]);
    }

    public function actionMoveCategoryAnnaAccesorii(int $id)
    {
        $this->moveRecord(Category::class, $id, 'category');
        return $this->redirectToListing('categories-anna-accesorii');
    }

    public function actionEditCategoryAnnaAccesorii(int $id)
    {
        $this->layout = 'admin';
        $category = $this->findCategory($id);
        $form = CategoryForm::fromCategory($category);
        if ($this->loadCategoryForm($form) && $form->validate()
            && $this->saveCategory($category, $form)) {
            return $this->redirect(['/admin/categories-anna-accesorii']);
        }
        return $this->render('category-form', ['form' => $form, 'category' => $category]);
    }

    public function actionDeleteCategoryAnnaAccesorii(int $id)
    {
        $category = $this->findCategory($id);
        if (Products::find()->where(['category_id' => $id])->exists()) {
            Yii::$app->session->setFlash('error', 'Categoria conține produse și nu poate fi ștearsă.');
            return $this->redirect(['/admin/categories-anna-accesorii']);
        }
        $name = $category->image;
        try {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$category->delete()) {
                    throw new \RuntimeException('Categoria nu a putut fi ștearsă.');
                }
                $transaction->commit();
            } catch (\Throwable $error) {
                $transaction->rollBack();
                throw $error;
            }
            try {
                $this->removeUnusedCategoryFile($name);
            } catch (\Throwable $error) {
                Yii::error($error, __METHOD__);
            }
            Yii::$app->session->setFlash('success', $category->title . ' a fost ștearsă.');
        } catch (\Throwable $error) {
            Yii::error($error, __METHOD__);
            Yii::$app->session->setFlash('error', 'Categoria nu a putut fi ștearsă.');
        }
        return $this->redirect(['/admin/categories-anna-accesorii']);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['/admin/admin-anna-accesorii']);
    }

    private function moveRecord(string $modelClass, int $id, string $table): void
    {
        $direction = Yii::$app->request->post('direction');

        if (!in_array($direction, ['up', 'down'], true)) {
            throw new BadRequestHttpException('Direcția aleasă nu este validă.');
        }

        $lockName = 'anna-sort-' . $table;
        $this->acquireSortLock($lockName);

        try {
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $ids = Yii::$app->db->createCommand(
                    "SELECT id FROM {{%{$table}}} ORDER BY sort_order, id FOR UPDATE"
                )->queryColumn();

                $position = array_search($id, array_map('intval', $ids), true);

                if ($position === false) {
                    throw new NotFoundHttpException('Elementul nu a fost găsit.');
                }

                $otherPosition = $position + ($direction === 'up' ? -1 : 1);

                if (isset($ids[$otherPosition])) {
                    [$ids[$position], $ids[$otherPosition]] = [$ids[$otherPosition], $ids[$position]];

                    foreach ($ids as $index => $recordId) {
                        $modelClass::updateAll(['sort_order' => $index + 1], ['id' => $recordId]);
                    }
                }

                $transaction->commit();
            } catch (\Throwable $error) {
                $transaction->rollBack();
                throw $error;
            }
        } finally {
            $this->releaseSortLock($lockName);
        }
    }

    private function acquireSortLock(string $name): void
    {
        $acquired = Yii::$app->db->createCommand(
            'SELECT GET_LOCK(:name, 10)',
            [':name' => $name]
        )->queryScalar();

        if ((int) $acquired !== 1) {
            throw new \RuntimeException('Ordinea nu poate fi modificată momentan.');
        }
    }

    private function releaseSortLock(string $name): void
    {
        Yii::$app->db->createCommand(
            'SELECT RELEASE_LOCK(:name)',
            [':name' => $name]
        )->queryScalar();
    }

    private function redirectToListing(string $action)
    {
        $page = Yii::$app->request->post('page', 1);
        $page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;

        return $this->redirect(['/admin/' . $action, 'page' => $page]);
    }

    private function loadProductForm(ProductForm $form): bool
    {
        if (!Yii::$app->request->isPost || !$form->load(Yii::$app->request->post())) {
            return false;
        }
        $form->images = UploadedFile::getInstances($form, 'images');
        return true;
    }

    private function loadCategoryForm(CategoryForm $form): bool
    {
        if (!Yii::$app->request->isPost || !$form->load(Yii::$app->request->post())) {
            return false;
        }
        $form->image = UploadedFile::getInstance($form, 'image');
        return true;
    }

    private function renderProductForm(ProductForm $form, Products $product)
    {
        return $this->render('product-form', [
            'form' => $form,
            'product' => $product,
            'categories' => Category::find()->orderBy(['title' => SORT_ASC])->all(),
        ]);
    }

    private function saveProduct(Products $product, ProductForm $form): bool
    {
        $storage = new AdminImageStorage();
        $newPaths = [];
        try {
            $newPaths = $storage->storeProducts($form->images);
            $oldPaths = $this->persistProduct($product, $form, $newPaths);
            try {
                $this->removeUnusedProductFiles($oldPaths);
            } catch (\Throwable $error) {
                Yii::error($error, __METHOD__);
            }
            return true;
        } catch (\Throwable $error) {
            foreach ($newPaths as $path) {
                $storage->deleteManagedProduct($path);
            }
            Yii::error($error, __METHOD__);
            $form->addError('images', 'Produsul nu a putut fi salvat. Încearcă din nou.');
            return false;
        }
    }

    /** @return string[] Removed image paths. */
    private function persistProduct(Products $product, ProductForm $form, array $newPaths): array
    {
        $isNew = $product->isNewRecord;

        if ($isNew) {
            $this->acquireSortLock('anna-sort-products');
        }

        $transaction = null;
        try {
            $transaction = Yii::$app->db->beginTransaction();
            $removedIds = array_map('intval', $form->removeImageIds);
            $retained = [];
            $removedPaths = [];
            foreach ($product->images as $image) {
                if (in_array((int) $image->id, $removedIds, true)) {
                    $removedPaths[] = $image->image;
                    $image->delete();
                } else {
                    $retained[] = $image;
                }
            }
            if (!$product->images && $product->image) {
                if ($form->removeLegacyImage) {
                    $removedPaths[] = $product->image;
                } else {
                    $retained[] = new ProductImages(['image' => $product->image]);
                }
            }
            $paths = array_merge(array_map(static fn (ProductImages $image): string => $image->image, $retained), $newPaths);
            $product->setAttributes([
                'title' => $form->title, 'subtitle' => $form->subtitle, 'description' => $form->description,
                'price' => $form->price, 'category_id' => $form->category_id,
                'image' => $paths[0],
            ]);

            if ($isNew) {
                $product->sort_order = (int) Products::find()->max('sort_order') + 1;
            }

            if (!$product->save(false)) {
                throw new \RuntimeException('Produsul nu a putut fi salvat.');
            }
            foreach ($paths as $order => $path) {
                $image = $retained[$order] ?? new ProductImages();
                $image->product_id = $product->id;
                $image->image = $path;
                $image->sort_order = $order;
                if (!$image->save(false)) {
                    throw new \RuntimeException('Imaginea produsului nu a putut fi salvată.');
                }
            }
            $transaction->commit();
            return $removedPaths;
        } catch (\Throwable $error) {
            if ($transaction !== null && $transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        } finally {
            if ($isNew) {
                $this->releaseSortLock('anna-sort-products');
            }
        }
    }

    private function saveCategory(Category $category, CategoryForm $form): bool
    {
        $storage = new AdminImageStorage();
        $newName = null;
        $isNew = $category->isNewRecord;
        $lockAcquired = false;
        try {
            if ($form->image !== null) {
                $newName = $storage->storeCategory($form->image);
            }
            $oldName = $category->image;
            $category->title = $form->title;
            if ($newName !== null) {
                $category->image = $newName;
            }

            if ($isNew) {
                $this->acquireSortLock('anna-sort-category');
                $lockAcquired = true;
                $category->sort_order = (int) Category::find()->max('sort_order') + 1;
            }

            if (!$category->save(false)) {
                throw new \RuntimeException('Categoria nu a putut fi salvată.');
            }
            if ($newName !== null) {
                try {
                    $this->removeUnusedCategoryFile($oldName);
                } catch (\Throwable $error) {
                    Yii::error($error, __METHOD__);
                }
            }
            return true;
        } catch (\Throwable $error) {
            if ($newName !== null) {
                $storage->deleteManagedCategory($newName);
            }
            Yii::error($error, __METHOD__);
            $form->addError('image', 'Categoria nu a putut fi salvată. Încearcă din nou.');
            return false;
        } finally {
            if ($lockAcquired) {
                $this->releaseSortLock('anna-sort-category');
            }
        }
    }

    private function removeUnusedProductFiles(array $paths): void
    {
        $storage = new AdminImageStorage();
        foreach (array_filter(array_unique($paths)) as $path) {
            if (!Products::find()->where(['image' => $path])->exists()
                && !ProductImages::find()->where(['image' => $path])->exists()
                && !OrderItem::find()->where(['product_image' => $path])->exists()) {
                $storage->deleteManagedProduct($path);
            }
        }
    }

    private function removeUnusedCategoryFile(?string $name): void
    {
        if ($name !== null && !Category::find()->where(['image' => $name])->exists()) {
            (new AdminImageStorage())->deleteManagedCategory($name);
        }
    }

    private function findProduct(int $id): Products
    {
        $product = Products::find()->with('images')->where(['id' => $id])->one();
        if ($product === null) {
            throw new NotFoundHttpException('Produsul nu a fost găsit.');
        }
        return $product;
    }

    private function findCategory(int $id): Category
    {
        $category = Category::findOne($id);
        if ($category === null) {
            throw new NotFoundHttpException('Categoria nu a fost găsită.');
        }
        return $category;
    }
}
