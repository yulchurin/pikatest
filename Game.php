<?php

class Game
{
    private $players;
    private $step;
    private $stopGame;
    public static $stopAction;

    public function __construct($array)
    {
        $this->players = [];
        $this->addPlayers($array);
    }

    public function play()
    {
        $_SESSION['deck'] = (new Cards(rand(1, 0xffff)))->deck;
        $this->dealCards();
        $this->sortAllPlayersCards();
        $this->showPlayers();
        while ($this->stopGame !== 1) {
            //unset($this->status);
            foreach ($this->players as $key => $player) {
                if (count($player->cards) === 0) {
                    unset($this->players[$key]);
                    $this->players = array_values($this->players);
                }
            }
            if (count($this->players) === 0) {
                echo '<strong>ничья</strong>';
                $this->stopGame = 1;
                break;
            }
            if (count($this->players) === 1) {
                echo '<strong>дурак: ', $this->players[0]->name, '</strong>';
                $this->stopGame = 1;
                break;
            }
            for ($i = 0; $i < Player::$counter; $i++) {
                ++$this->step;
                echo '<pre><strong>', $this->step, ': ';
                // пропуск хода если поднял карты
                if ($this->players[$i]->skipStep === 1) {
                    $i += 1;
                    if ($i >= Player::$counter) {
                        $i = 0;
                    }
                }
                $j = $i + 1;
                if ($j === Player::$counter) {
                    $j = 0;
                }
                echo ($this->players[$i])->name, ' versus ', ($this->players[$j])->name, '</strong></pre>';
                ($this->players[$i])->showCards();
                if (count($this->players) > 1) {
                    ($this->players[$j])->showCards();
                }
                if (count($this->players) > 1) {
                    $this->players[$i]->doFirstStep();
                    Game::$stopAction = 0;
                } else {
                    Game::$stopAction = 1;
                }
                while (Game::$stopAction !== 1) {
                    ($this->players[$j])->defense();
                    if (count($this->players[$j]->cards) !== 0) {
                        ($this->players[$i])->attack();
                    }
                }
                $this->allPlayersTakeCardsFromDeck();
                $this->removePlayersWithNoCards();
            }
        }
    }

    public function showPlayers()
    {
        echo '<pre>';
        foreach ($this->players as $player) {
            $player->showCards();
        }
        echo '</pre>';
    }

    public function addPlayers($names = [])
    {
        foreach ($names as $name) {
            $this->players[] = new Player($name);
        }
    }

    private function removePlayersWithNoCards()
    {
        if (count($_SESSION['deck']) === 0) {
            foreach ($this->players as $key => $player) {
                if (count($player->cards) === 0) {
                    unset($this->players[$key]);
                    $this->players = array_values($this->players);
                    --Player::$counter;
                }
            }
        }
    }

    public function dealCards()
    {
        //раздаем карты в начале игры
        /*
        * После сортировки колоды выполняется 6 итераций раздачи карт
        * из начала массива колоды: для каждого игрока, в порядке их добавления в игру,
        * достается карта из начала колоды (начало массива).
        * Таким образом после 6 итераций у каждого игрока должно быть 6 карт в руках;
        */
        if (Player::$counter < 2 || Player::$counter > 4) {
            exit('Количество игороков должно быть от 2 до 4');
        }
        for ($i = 0; $i < 6; $i++) {
            foreach ($this->players as $player) {
                $player->takeCardFromDeck(1);
            }
        }
        //определяем козырь
        global $trump;
        $trump = array_shift($_SESSION['deck']);
        //помещаем козырную карту в конец колоды
        array_push($_SESSION['deck'], $trump);
        echo 'Масть козырной карты: <strong>', $trump['mast'], '</strong>';
    }

    public function sortAllPlayersCards()
    {
        foreach ($this->players as $player) {
            $player->sortPlayerCards();
        }
    }

    private function allPlayersTakeCardsFromDeck()
    {
        // добираем карты
        if (count($_SESSION['deck']) !== 0) {
            foreach ($this->players as $player) {
                if (count($player->cards) < 6) {
                    while (count($player->cards) < 6 && count($_SESSION['deck']) !== 0) {
                        $player->takeCardFromDeck();
                    }
                }
            }
        }
    }
}
