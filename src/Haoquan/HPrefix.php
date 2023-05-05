<?php

namespace Haoquan;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerChatEvent;

use jojoe77777\FormAPI\FormAPI;
use onebone\economyapi\EconomyAPI;

class HPrefix extends PluginBase implements Listener {
    
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("稱號插件 - Made by Haoquan Liu");
        $formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $econapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if($formapi == null && $econapi == null) {
            $this->getLogger()->info("未安裝插件「FormAPI」以及「EconomyAPI」，為避免錯誤已停用此插件。");
            $this->getPluginLoader()->disablePlugin($this);
        }
        else if($formapi == null) {
            $this->getLogger()->info("未安裝插件「FormAPI」，為避免錯誤已停用此插件。");
            $this->getPluginLoader()->disablePlugin($this);
        }
        else if($econapi == null) {
            $this->getLogger()->info("未安裝插件「EconomyAPI」，為避免錯誤已停用此插件。");
            $this->getPluginLoader()->disablePlugin($this);
        }
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "/Players");
        $this->config = new Config($this->getDataFolder()."prefixes.yml", Config::YAML);
        if($this->config->getAll() == null) {
            $cmd_on = array('say %p is handsome.', 'say %p is very handsome.');
            $cmd_off = array('say %p is ugly.', 'say %p is very ugly.');
            $default_config["name"] = "測試稱號";
            $default_config["payment"] = "eco";
            $default_config["price"] = 100;
            $default_config["cmd_on"] = $cmd_on;
            $default_config["cmd_off"] = $cmd_off;
            $this->config->set("測試稱號", $default_config);
            $this->config->save();
        }
    }
    
    public function onChat(PlayerChatEvent $event) : void {
        $playername = $event->getPlayer()->getName();
        $playernick = $this->getPrefix($event->getPlayer());
        if($playernick) {
            $event->setFormat('[' . $playernick . TextFormat::RESET . '] ' . $playername . ': ' . $event->getMessage());
        }
        else {
            $event->setFormat($playername . ': ' . $event->getMessage());
        }
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        if($sender instanceof Player) {
            switch($command->getName()) {
                case "hprefix":
                case "hp":
                    if(!isset($args[0])) {
                        if($sender->hasPermission("hprefix.command.hprefix")) {
                            $this->showMainForm($sender);
                        }
                        else {
                            $sender->sendMessage("您沒有權限執行此指令");
                        }
                    }
                    else {
                        switch($args[0]) {
                            case 'create':
                                if($sender->hasPermission("hprefix.command.create")) {
                                    if(isset($args[1])) {
                                        $this->config = new Config($this->getDataFolder()."prefixes.yml", Config::YAML);
                                        $default_config["name"] = $args[1];
                                        $default_config["payment"] = "eco";
                                        $default_config["price"] = 100;
                                        $default_config["cmd_on"] = "say %p is handsome.";
                                        $default_config["cmd_off"] = "say %p is ugly.";
                                        $this->config->set($args[1], $default_config);
                                        $this->config->save();
                                        $sender->sendMessage("成功創建稱號｢" . $args[1] . TextFormat::RESET . "」，請到 prefixes.yml 設定。");
                                    }
                                    else {
                                        $sender->sendMessage("正確用法: /hp create <稱號>");
                                    }
                                }
                                else {
                                    $sender->sendMessage("您沒有權限執行此指令");
                                }
                                return true;
                            
                            case 'remove':
                                if($sender->hasPermission("hprefix.command.remove")) {
                                    if(isset($args[1])) {
                                        $this->config = new Config($this->getDataFolder()."prefixes.yml", Config::YAML);
                                        $this->config->remove($args[1]);
                                        $this->config->save();
                                        $sender->sendMessage("成功移除稱號｢" . $args[1] . TextFormat::RESET . "」 !");
                                    }
                                    else {
                                        $sender->sendMessage("正確用法: /hp remove <稱號>");
                                    }
                                }
                                else {
                                    $sender->sendMessage("您沒有權限執行此指令");
                                }
                                return true;
                        }
                    }
                    return true;
                                       
                default:
                    return false;
            }
        }
        else {
            $sender->sendMessage("[HPrefix] 請在遊戲中執行此指令。");
            return false;
        }
    }
    
    public function showMainForm(Player $player) : void {
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data) {
            $sdata = (string)$data;
            if($sdata != null) {
                switch($sdata) {
                    case '0':
                        $this->showManageForm($player);
                        return true;
                    case '1':
                        $this->showShopForm($player);
                        return true;
                }
            }
        });
        $form->setTitle("稱號商店");
        $form->setContent("請選擇您要執行的動作");
        $form->addButton("<管理稱號>");
        $form->addButton("<購買稱號>");
        $form->sendToPlayer($player);
    }
    
    public function showManageForm(Player $player) : void {
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $this->player = new Config($this->getDataFolder() . "/Players/" . $player->getName() . ".yml", Config::YAML);
        $list = $this->player->get("list", array());
        if($list != null) {
            $form = $api->createCustomForm(function (Player $player, $data) {
                $sdata = (string)$data[0];
                if($sdata != null) {
                    if($data[1] == true) {
                        $this->player = new Config($this->getDataFolder() . "/Players/" . $player->getName() . ".yml", Config::YAML);
                        $this->config = new Config($this->getDataFolder()."prefixes.yml", Config::YAML);
                        $prefix = $this->player->get('nowon');
                        if($prefix) {
                            foreach($this->config->get($prefix)['cmd_off'] as $command) {
                                $cmd = str_replace('%p', $player->getName(), $command);
                                $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                            }
                            $this->player->remove("nowon");
                            $this->player->save();
                            $player->sendMessage("成功隱藏稱號!");
                        }
                        else {
                            $player->sendMessage("您的稱號已經被隱藏了。");
                        }
                    }
                    else {
                        $this->player = new Config($this->getDataFolder() . "/Players/" . $player->getName() . ".yml", Config::YAML);
                        $list = $this->player->get("list", array());
                        $prefix = $list[$data[0]];
                        $this->setPrefix($player, $prefix);
                    }
                }
                else {
                    $this->showMainForm($player);
                }
            });
            $form->addDropdown("請選擇您想設置的稱號", $list);
            $form->addToggle("隱藏稱號");
        }
        else {
            $form = $api->createSimpleForm(function (Player $player, $data) {
                $sdata = (string)$data;
                if($sdata != null) {
                    switch($sdata) {
                        case '0':
                            $this->showMainForm($player);
                    }
                }
                else {
                    $this->showMainForm($player);
                }
            });
            $form->setContent("您尚未擁有任何稱號。");
            $form->addButton("<返回上頁>");
            $form->addButton("<關閉選單>");
        }
        $form->setTitle("管理稱號");
        $form->sendToPlayer($player);
    }
    
    public function showShopForm(Player $player) : void {
        $this->config = new Config($this->getDataFolder()."prefixes.yml", Config::YAML);
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data) {
            $this->player = new Config($this->getDataFolder() . "/Players/" . $player->getName() . ".yml", Config::YAML);
            $sdata = (string)$data;
            if($sdata != null) {
                $all_config = $this->config->getAll();
                $ps = array();
                foreach($all_config as $cfg) {
                    array_push($ps, $cfg["name"]);
                }
                for($i = 0; $i < (count($all_config) + 1); $i++) {
                    if($sdata == (string)$i) {
                        $list = $this->player->get("list", array());
                        if(!in_array($ps[$i], $list)) {
                            $this->buy = new Config($this->getDataFolder()."dontchange.yml", Config::YAML);
                            if($this->buy->get($player->getName())) {
                                $this->buy->remove($player->getName());
                                $this->buy->save();
                            }
                            $this->buy->set($player->getName(), $ps[$i]);
                            $this->buy->save();
                            $this->showConfirmForm($player);
                        }
                        else {
                            $this->showHadForm($player);
                        }
                    }
                }
            }
            else {
                $this->showMainForm($player);
            }
        });
        $form->setTitle("購買稱號");
        $form->setContent("請選擇您想購買的稱號");
        $prefixes = $this->config->getAll();
        foreach($prefixes as $prefix) {
            $form->addButton($prefix["name"]);
        }
        $form->sendToPlayer($player);
    }

    public function showConfirmForm(Player $player) : void {
        $this->config = new Config($this->getDataFolder()."prefixes.yml", Config::YAML);
        $prefix = $this->buy->get($player->getName());
        $price = $this->config->get($prefix)['price'];
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createModalForm(function (Player $player, $data) {
            if($data == true) {
                $this->purchasePrefix($player);
                return true;
            }
            return;
        });
        $form->setTitle("購買稱號");
        $form->setContent("您確定要花費 $" . $price . " 購買稱號｢" . $prefix . TextFormat::RESET . "」嗎？");
        $form->setButton1("確定");
        $form->setButton2("取消");
        $form->sendToPlayer($player);
                    
    }
    
    public function showHadForm(Player $player) : void {
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data) {
            $sdata = (string)$data;
            if($sdata != null) {
                switch($sdata) {
                    case '0':
                        $this->showMainForm($player);
                }
            }
        });
        $form->setTitle("購買稱號");
        $form->setContent("您已擁有此稱號。");
        $form->addButton("<返回主頁>");
        $form->addButton("<關閉選單>");
        $form->sendToPlayer($player);
    }
    
    public function purchasePrefix(Player $player) : void {
        $this->buy = new Config($this->getDataFolder() . "dontchange.yml", Config::YAML);
        $this->config = new Config($this->getDataFolder()."prefixes.yml", Config::YAML);
        $prefix = $this->buy->get($player->getName());
        $price = $this->config->get($prefix)['price'];
        $result = EconomyAPI::getInstance()->reduceMoney($player, (int)$price);
        if($this->config->get($prefix)['payment'] == 'eco') {
            if($result == EconomyAPI::RET_INVALID) {
                $this->buy->remove($player->getName());
                $this->buy->save();
                $player->sendMessage("您沒有足夠的金錢購買稱號｢" . $prefix . TextFormat::RESET . "」。");
            }
            else {
                $this->successfullyPurchase($player);
            }
        }
    }
    
    public function successfullyPurchase($player) : void {
        $this->buy = new Config($this->getDataFolder() . "dontchange.yml", Config::YAML);
        $this->player = new Config($this->getDataFolder() . "/Players/" . $player->getName() . ".yml", Config::YAML);
        $prefix = $this->buy->get($player->getName());
        $list = $this->player->get("list", array());
        array_push($list, $prefix);
        $this->player->set("list", $list); 
        $this->player->save();
        $player->sendMessage("您已成功購買稱號｢" . $prefix . TextFormat::RESET . "」 !");
        $this->buy->remove($player->getName());
        $this->buy->save();
    }
    
    public function setPrefix(Player $player, string $prefix) : void {
        $this->player = new Config($this->getDataFolder() . "/Players/" . $player->getName() . ".yml", Config::YAML);
        $now = $this->player->get("nowon");
        if($now == $prefix) {
            $player->sendMessage("您目前的稱號已經是｢" . $prefix . TextFormat::RESET . "」了。");
        }
        else {
            $this->config = new Config($this->getDataFolder()."prefixes.yml", Config::YAML);
            foreach($this->config->get($prefix)['cmd_on'] as $command) {
                $cmd = str_replace('%p', $player->getName(), $command);
                $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmd);
            }
            $this->player->set("nowon", $prefix);
            $this->player->save();
            $player->sendMessage("成功設置稱號｢" . $prefix . TextFormat::RESET . "」 !");
        }
    }
    
    public function getPrefix(Player $player) : string {
        $this->player = new Config($this->getDataFolder() . "/Players/" . $player->getName() . ".yml", Config::YAML);
        $prefix = $this->player->get("nowon");
        if($prefix) {
            return $prefix;
        }
        else {
            return false;
        }
    }
}
?>
