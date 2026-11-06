<?php

namespace app\commands;

use app\models\AdminUser;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class AdminSetupController extends Controller
{
    public function actionCreate(string $email): int
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->stderr("Invalid email address.\n");

            return ExitCode::DATAERR;
        }

        if (AdminUser::find()->where(['email' => $email])->exists()) {
            $this->stderr("Administrator already exists.\n");

            return ExitCode::DATAERR;
        }

        $password = $this->readPassword('New password: ');
        $confirmation = $this->readPassword('Confirm password: ');

        if ($password !== $confirmation) {
            $this->stderr("Passwords do not match.\n");

            return ExitCode::DATAERR;
        }

        if (strlen($password) < 16) {
            $this->stderr("Password must contain at least 16 characters.\n");

            return ExitCode::DATAERR;
        }

        $admin = new AdminUser();

        $admin->email = $email;

        $admin->hashed_password = Yii::$app->security
            ->generatePasswordHash($password);

        if (!$admin->save()) {
            $this->stderr("Failed to create administrator.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Administrator created successfully.\n");

        return ExitCode::OK;
    }

    private function readPassword(string $label): string
    {
        $this->stdout($label);

        $stty = shell_exec('stty -g 2>/dev/null');

        if ($stty === null || trim($stty) === '') {
            throw new \RuntimeException(
                'A terminal with hidden input is required.'
            );
        }

        shell_exec('stty -echo');

        try {
            $password = fgets(STDIN);
        } finally {
            shell_exec('stty ' . escapeshellarg(trim($stty)));

            $this->stdout("\n");
        }

        if ($password === false) {
            throw new \RuntimeException(
                'Unable to read password.'
            );
        }

        return rtrim($password, "\r\n");
    }
}
