<?php

namespace Amenoyoya\PHPMailer\Facades;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * テキストメール送信 Facade
 */
class TextMailer
{
    private $mailer;

    public function __construct($config)
    {
        // PHPMailer: Exception有効状態でインスタンス化
        $this->mailer = new PHPMailer(true);
        //日本語用設定
        $this->mailer->CharSet = 'UTF-8';
        // SMTP を使用
        if (is_array(@$config['smtp'])) {
            $this->mailer->isSMTP();
            $this->mailer->Host       = @$config['smtp']['host'];
            $this->mailer->Port       = @$config['smtp']['port'] ?: 25;
            $this->mailer->SMTPAuth   = @$config['smtp']['auth'];
            $this->mailer->Username   = @$config['smtp']['username'];
            $this->mailer->Password   = @$config['smtp']['password'];
            $this->mailer->SMTPSecure = @$config['smtp']['secure'];
        }
        // 送信元設定
        $this->mailer->From = is_array($config['from'])? $config['from']['email']: $config['from'];
        $this->mailer->FromName = @$config['from']['name'];
    }

    /**
     * メール送信
     * @param string|array $to: 送信先アドレス
     * @param string $title: タイトル
     * @param string $view: 本文ビューファイル
     * @param array $variables: 埋め込み変数
     */
    public function send($to, $title, $view, $variables = [])
    {
        $this->mailer->clearAllRecipients();
        if (is_array($to)) {
            foreach ($to as $addr) {
                $this->mailer->addAddress($addr);
            }
        } else {
            $this->mailer->addAddress($to);
        }
        $this->mailer->Subject = $title;
        $this->mailer->Body = view($view)->with($variables);
        if (!$this->mailer->send()) {
            throw new Exception($this->mailer->ErrorInfo);
        }
    }
}
