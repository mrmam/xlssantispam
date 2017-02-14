<?php
/**
    Copyright (C) 2016-2017 Hunter Ashton

    This file is part of BruhhBot.

    BruhhBot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    BruhhBot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */
function saveme($update, $MadelineProto, $msg, $name)
{
    global $responses, $engine;
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
            );
        $mods = $responses['saveme']['mods'];
        if ($peerUSER
            or from_admin_mod($update, $MadelineProto, $mods, true)
        ) {
            if ($name) {
                if ($name == "from") {
                    if ($msg) {
                        savefrom($update, $MadelineProto, $msg);
                        return;
                    } else {
                        savefrom($update, $MadelineProto, false);
                        return;
                    }
                }
            }
            if (strlen($msg) < 2000) {
                if ($name && $msg) {
                    var_dump(strlen($msg));
                    $codename = "<code>$name</code>";
                    check_json_array('saved.json', $ch_id);
                    $file = file_get_contents("saved.json");
                    $saved = json_decode($file, true);
                    if (array_key_exists($ch_id, $saved)) {
                        if (!array_key_exists("from", $saved[$ch_id])) {
                            $saved[$ch_id]["from"] = [];
                        }
                        if (array_key_exists($name, $saved[$ch_id]["from"])) {
                            unset($saved[$ch_id]["from"][$name]);
                        }
                        $saved[$ch_id][$name] = $msg;
                        file_put_contents('saved.json', json_encode($saved));
                        $str = $responses['saveme']['success'];
                        $repl = array(
                            "name" => $name
                        );
                        $message = $engine->render($str, $repl);
                        $default['message'] = $message;
                    } else {
                        $saved[$ch_id] = [];
                        $saved[$ch_id]["from"] = [];
                        $saved[$ch_id][$name] = $msg;
                        file_put_contents('saved.json', json_encode($saved));
                        $str = $responses['saveme']['success'];
                        $repl = array(
                            "name" => $name
                        );
                        $message = $engine->render($str, $repl);
                        $default['message'] = $message;
                    }
                } else {
                    $message = $responses['saveme']['help'];
                    $default['message'] = $message;
                }
                if (isset($default['message'])) {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
                if (isset($sentMessage)) {
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}

function getme($update, $MadelineProto, $name)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
            );
        check_json_array('saved.json', $ch_id);
        $file = file_get_contents("saved.json");
        $saved = json_decode($file, true);
        $boldname = html_bold($name);
        if (array_key_exists($ch_id, $saved)) {
            if ($name !== "from") {
                foreach ($saved[$ch_id] as $i => $ii) {
                    if (!is_array($i)) {
                        if ($i == $name) {
                            $message = "$boldname:\r\n".$saved[$ch_id][$i];
                            $default['message'] = $message;
                        }
                    }
                }
            }
            if (!isset($message)) {
                if (array_key_exists("from", $saved[$ch_id])) {
                    foreach ($saved[$ch_id]["from"] as $i => $ii) {
                        if ($i == $name) {
                            $replyid = $ii["msgid"];
                            $replychat = $ii["chat"];
                            break;
                        }
                    }
                }
            }
            if (isset($message)) {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
            if (isset($replyid)) {
                $sentMessage =$MadelineProto->messages->forwardMessages(
                    ['from_peer' => $replychat, 'id' => [$replyid], 'to_peer' =>
                    $peer, ]
                );
            }
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function savefrom($update, $MadelineProto, $name)
{
    global $responses, $engine;
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    $replyto = $update['update']['message']['id'];
    $mods = $responses['savefrom']['mods'];
    $default = array(
        'peer' => $peer,
        'reply_to_msg_id' => $replyto,
        'parse_mode' => 'html',
    );
    if ($peerUSER or from_admin_mod($update, $MadelineProto, $mods, true)) {
        if ($name !== "from") {
            if (array_key_exists("reply_to_msg_id", $update["update"]["message"])) {
                $msg_id = $update['update']['message']["reply_to_msg_id"];
                check_json_array('saved.json', $ch_id);
                $file = file_get_contents("saved.json");
                $saved = json_decode($file, true);
                if (array_key_exists($ch_id, $saved)) {
                    if (!array_key_exists("from", $saved[$ch_id])) {
                        $saved[$ch_id]["from"] = [];
                    }
                    $saved[$ch_id]["from"][$name] = [];
                    $saved[$ch_id]["from"][$name]["chat"] = $ch_id;
                    $saved[$ch_id]["from"][$name]["msgid"] = $msg_id;
                    if (array_key_exists($name, $saved[$ch_id])) {
                        unset($saved[$ch_id][$name]);
                    }
                    file_put_contents('saved.json', json_encode($saved));
                    $str = $responses['savefrom']['success'];
                    $repl = array(
                        "name" => $name
                    );
                    $message = $engine->render($str, $repl);
                    $default['message'] = $message;
                } else {
                    $saved[$ch_id] = [];
                    $saved[$ch_id]["from"] = [];
                    $saved[$ch_id]["from"][$name] = [];
                    $saved[$ch_id]["from"][$name]["chat"] = $ch_id;
                    $saved[$ch_id]["from"][$name]["msgid"] = $msg_id;
                    file_put_contents('saved.json', json_encode($saved));
                    $str = $responses['savefrom']['success'];
                    $repl = array(
                        "name" => $name
                    );
                    $message = $engine->render($str, $repl);
                    $default['message'] = $message;
                }
            } else {
                $message = $responses['savefrom']['help'];
                $default['message'] = $message;
            }
        }
    }
    if (isset($default['message'])) {
        $sentMessage = $MadelineProto->messages->sendMessage(
            $default
        );
    }
    if (isset($sentMessage)) {
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function saved_get($update, $MadelineProto)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $title = "this chat";
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
            );
        check_json_array('saved.json', $ch_id);
        $file = file_get_contents("saved.json");
        $saved = json_decode($file, true);
        if (array_key_exists($ch_id, $saved)) {
            foreach ($saved[$ch_id] as $i => $ii) {
                if ($i !== "from") {
                    if (!isset($message)) {
                        $message = "<b>Saved messages for $title:</b>\r\n<code>$i</code>\r\n";
                    } else {
                        $message = "$message<code>$i</code>\r\n";
                    }
                }
            }
            if (array_key_exists("from", $saved[$ch_id])) {
                foreach ($saved[$ch_id]["from"] as $i => $ii) {
                    if (!isset($message)) {
                        $message = "<b>Saved messages for $title:</b>\r\n$i\r\n";
                    } else {
                        $message = "$message<code>$i</code>\r\n";
                    }
                }
            }
            if (isset($message)) {
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function save_clear($update, $MadelineProto, $msg) {
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        if ($msg !== "from") {
            $msg_id = $update['update']['message']['id'];
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html',
                );
            $mods = "Why the hell would we let a normal user clear saved messages?";
            if ($peerUSER
                or from_admin_mod($update, $MadelineProto, $mods, true)
            ) {
                if ($msg) {
                    check_json_array('saved.json', $ch_id);
                    $file = file_get_contents("saved.json");
                    $saved = json_decode($file, true);
                    if (array_key_exists($ch_id, $saved)) {
                        if (!array_key_exists("from", $saved[$ch_id])) {
                            $saved[$ch_id]["from"] = [];
                        }
                        if (array_key_exists($msg, $saved[$ch_id]["from"])) {
                            unset($saved[$ch_id]["from"][$msg]);
                            $message = "<code>$msg</code> was successfully cleared";
                            $default['message'] = $message;
                            file_put_contents('saved.json', json_encode($saved));
                        } elseif (array_key_exists($msg, $saved[$ch_id])) {
                            unset($saved[$ch_id][$msg]);
                            $message = "<code>$msg</code> was successfully cleared";
                            $default['message'] = $message;
                            file_put_contents('saved.json', json_encode($saved));
                        } else {
                            $message = "<code>$msg</code> is not a saved message";
                            $default['message'] = $message;
                        }
                    } else {
                        $message = "<code>$msg</code> is not a saved message";
                        $default['message'] = $message;
                    }
                } else {
                    $message = "Use <code>/save clear message</code> to clear the contents of a message";
                    $default['message'] = $message;
                }
                if (isset($default['message'])) {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
                if (isset($sentMessage)) {
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            }
        }
    }
}
