<?php

namespace GirisKayitForm;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase implements Listener {

    private $dataFile;
    private $users = [];

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->dataFile = $this->getDataFolder() . "users.json";
        if(!is_dir($this->getDataFolder())) @mkdir($this->getDataFolder(), 0777, true);
        if(file_exists($this->dataFile)){
            $this->users = json_decode(file_get_contents($this->dataFile), true) ?? [];
        }
    }

    public function onDisable(): void {
        file_put_contents($this->dataFile, json_encode($this->users));
    }

    public function onJoin(PlayerJoinEvent $event) : void {
        $player = $event->getPlayer();
        $this->showMainMenu($player);
    }

    public function showMainMenu(Player $player) {
        $form = new SimpleForm(function (Player $player, ?int $data = null) {
            if ($data === null) return;
            switch($data) {
                case 0:
                    $this->showRegisterForm($player);
                    break;
                case 1:
                    $this->showLoginForm($player);
                    break;
                case 2:
                    $this->showChangePasswordForm($player);
                    break;
            }
        });
        $form->setTitle("Giriş/Kayıt Menüsü");
        $form->setContent("Lütfen bir seçim yap:");
        $form->addButton("Kayıt Ol");
        $form->addButton("Giriş Yap");
        $form->addButton("Şifre Değiştir");
        $player->sendForm($form);
    }

    public function showRegisterForm(Player $player) {
        $form = new CustomForm(function(Player $player, $data = null){
            if($data === null) return;
            $name = strtolower($player->getName());
            if(isset($this->users[$name])){
                $player->sendMessage("§cZaten kayıtlısın!");
                return;
            }
            $password = $data[0];
            $email = $data[1];
            if(strlen($password) < 4){
                $player->sendMessage("§cŞifre en az 4 karakter olmalı!");
                return;
            }
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                $player->sendMessage("§cGeçerli bir e-posta gir!");
                return;
            }
            $this->users[$name] = [
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "email" => $email
            ];
            file_put_contents($this->dataFile, json_encode($this->users));
            $player->sendMessage("§aKayıt başarılı! Şimdi giriş yapabilirsin.");
        });
        $form->setTitle("Kayıt Ol");
        $form->addInput("Şifre", "Şifreniz");
        $form->addInput("E-posta", "ornek@mail.com");
        $player->sendForm($form);
    }

    public function showLoginForm(Player $player) {
        $form = new CustomForm(function(Player $player, $data = null){
            if($data === null) return;
            $name = strtolower($player->getName());
            if(!isset($this->users[$name])){
                $player->sendMessage("§cÖnce kayıt olmalısın!");
                return;
            }
            $password = $data[0];
            if(password_verify($password, $this->users[$name]['password'])){
                $player->sendMessage("§aBaşarıyla giriş yaptın! İyi oyunlar.");
            } else {
                $player->sendMessage("§cŞifre yanlış!");
            }
        });
        $form->setTitle("Giriş Yap");
        $form->addInput("Şifre", "Şifreniz");
        $player->sendForm($form);
    }

    public function showChangePasswordForm(Player $player) {
        $form = new CustomForm(function(Player $player, $data = null){
            if($data === null) return;
            $name = strtolower($player->getName());
            if(!isset($this->users[$name])){
                $player->sendMessage("§cKayıtlı değilsin!");
                return;
            }
            $old = $data[0];
            $new = $data[1];
            if(!password_verify($old, $this->users[$name]['password'])){
                $player->sendMessage("§cEski şifre yanlış!");
                return;
            }
            if(strlen($new) < 4){
                $player->sendMessage("§cYeni şifre en az 4 karakter olmalı!");
                return;
            }
            $this->users[$name]['password'] = password_hash($new, PASSWORD_DEFAULT);
            file_put_contents($this->dataFile, json_encode($this->users));
            $player->sendMessage("§aŞifre başarıyla değiştirildi.");
        });
        $form->setTitle("Şifre Değiştir");
        $form->addInput("Eski Şifre", "Eski Şifreniz");
        $form->addInput("Yeni Şifre", "Yeni Şifreniz");
        $player->sendForm($form);
    }
}