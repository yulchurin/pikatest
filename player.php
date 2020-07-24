<?php
$dost = [
    6 => 6,
    7 => 7,
    8 => 8,
    9 => 9,
    10 => 10,
    11 => 'В',
    12 => 'Д',
    13 => 'К',
    14 => 'Т'
]; // для вывода
class Player
{
    public static $counter;
    public $name;
    public $cards = [];
    public $trumps = [];
    public $skipStep;

    public function __construct($name)
    {
        $this->name = $name;
        ++self::$counter;
    }

    public function takeCardFromDeck($silence = 0)
    {
        global $dost;
        if (count($_SESSION['deck']) !== 0) {
            $newCard = array_shift($_SESSION['deck']);
            $this->cards[] = $newCard;
        }
        if (!$silence) {
            echo '<pre>(deck) ', $this->name, ' + ', $dost[$newCard['dost']], $newCard['mast'], '</pre>';
        }
        $this->sortPlayerCards();
    }

    public function sortPlayerCards()
    {
        global $trump;
        /*
         * На протяжении всей игры карты в руках игроков должны быть сортированы.
         * Вначале идут карты всех не козырных мастей, сортированные по достоинству и по масти
         * (порядок: пика, крест, бубен, червей), затем козыри, также сортированные по достоинству;
         * */
        $masti = ['♠', '♣', '♦', '♥'];
        // отсортируем масти так, чтобы козырная масть осталась в конце
        foreach ($masti as $key => $mast) {
            if ($trump['mast'] === $mast) {
                unset($masti[$key]);
                array_push($masti, $trump['mast']);
            }
        }
        // сортируем карты по достоинству
        global $faces;
        foreach ($faces as $dost) {
            foreach ($this->cards as $key => $card) {
                if ($card['dost'] === $dost) {
                    $temp = ($this->cards[$key]);
                    unset($this->cards[$key]);
                    array_push($this->cards, $temp);
                    unset($temp);
                }
            }
        }
        // сортируем карты по масти
        foreach ($masti as $mast) {
            foreach ($this->cards as $key => $card) {
                if ($card['mast'] === $mast) {
                    $temp = ($this->cards[$key]);
                    unset($this->cards[$key]);
                    array_push($this->cards, $temp);
                    unset($temp);
                }
            }
        }
        $this->updateArrayKeys();
    }

    public function showCards()
    {
        global $dost;
        if (count($this->cards) !== 0) {
            echo '<pre>', $this->name, ': ';
            foreach ($this->cards as $card) {
                echo $dost[$card['dost']], $card['mast'], ' ';
            }
            echo'</pre>';
        }
    }

    private function sortOutTrumps()
    {
        global $trump;
        foreach ($this->cards as $key => $card) {
            if ($card['mast'] === $trump['mast']) {
                $this->trumps[] = $card;
                unset($this->cards[$key]);
            }
        }
    }

    private function mergeWithTrumps()
    {
        if (isset($this->trumps) && count($this->trumps) !== 0) {
            $this->cards = array_merge($this->cards, $this->trumps);
        }
        unset($this->trumps);
    }

    private function sortByFaces()
    {
        global $faces;
        foreach ($faces as $face) {
            foreach ($this->cards as $key => $card) {
                if ($card['dost'] === $face) {
                    $temp = ($this->cards[$key]);
                    unset($this->cards[$key]);
                    array_push($this->cards, $temp);
                    unset($temp);
                }
            }
        }
    }

    private function updateArrayKeys()
    {
        $this->cards = array_values($this->cards);
    }

    private function fillCards()
    {
        if (count($this->cards) < 6) {
            while (count($this->cards) < 6) {
                $this->takeCardFromDeck();
            }
        }
    }

    public function doFirstStep()
    {
        unset($_SESSION['gameBoard']);
        // делаем первый ход
        // временно разбиваем на отдельные массивы обычные карты и козырные
        $this->sortOutTrumps();
        // отсортируем карты по достоинству
        $this->sortByFaces();
        // обнуляем ключи массива с картами, как никак 2020 год...
        $this->updateArrayKeys();
        if (!empty($this->cards)) {
            // игрок нападает самой своей мелкой картой
            $step = $this->cards[0];
            $_SESSION['gameBoard'][] = $step; // массив в котором находятся все карты одного хода
            unset($this->cards[0]);        // убираем эту карту у игрока
            // переиндексовываем массив карт игрока
            $this->updateArrayKeys();
        } else {
            // идем козырными
            if (!empty($this->trumps)) {
                $step = $this->trumps[0];
                $_SESSION['gameBoard'][] = $step;
                unset($this->trumps[0]);
                if (count($this->trumps) !== 0) {
                    $this->trumps = array_values($this->trumps);
                }
            } else {
                $this->skipStep = 1;
            }
        }
        echo '<pre>', $this->name, ' --> ', $GLOBALS['dost'][$step['dost']], $step['mast'], '</pre>';
        $this->mergeWithTrumps();
        $this->sortPlayerCards();
    }

    public function attack()
    {
        // подкидываем карты
        $iHaveCardsForYou = 0;
        $facesInGame = [];
        // создаем массив с достоинствами карт в игре (на доске)
        if (isset($_SESSION['gameBoard']) && count($_SESSION['gameBoard']) !== 0) {
            foreach ($_SESSION['gameBoard'] as $card) {
                $facesInGame[] = $card['dost'];
            }
            array_unique($facesInGame);
        } else {
            Game::$stopAction = 1;
        }
        /*
         * проверим есть ли карты чтобы докинуть их:
         * закинем в массив карты с достоинствами которые есть на игровой доске
         * и если количество элементов массива буден не 0, значит есть что докинуть
         * в дальнейшем из массива карт игрока постепенно карты будут удаляться, так что в конце концов
         * количество элементов вэтом массиве будет 0
        */
        foreach ($facesInGame as $face) {
            foreach ($this->cards as $card) {
                if ($card['dost'] === $face) {
                    $iHaveCardsForYou = 1;
                }
            }
        }
        if ($iHaveCardsForYou !== 0) {
            $breakStep = 0;
            foreach ($facesInGame as $face) {
                if ($breakStep === 1) {
                    break;
                }
                foreach ($this->cards as $key => $card) {
                    if ($card['dost'] === $face) {
                        $_SESSION['gameBoard'][] = $this->cards[$key];
                        echo '<pre>', $this->name, ' --> ', $GLOBALS['dost'][$card['dost']], $card['mast'], '</pre>';
                        unset($this->cards[$key]);
                        $breakStep = 1;
                        break;
                    }
                }
            }
        } else {
            Game::$stopAction = 1;
        }
    }

    public function defense()
    {
        $repeled = 0; // отбито или нет
        $this->skipStep = 0;
        // временно разбиваем на отдельные массивы обычные карты и козырные
        $this->sortOutTrumps();
        if (count($_SESSION['gameBoard']) !== 0) {
            $cardToRepel = end($_SESSION['gameBoard']);
        }
        /*
         * по идее последняя карта в массиве gameBoard должна быть от атакующего
        */
        if (Game::$stopAction !== 1) {
            foreach ($this->cards as $key => $card) {
                if ($card['mast'] === $cardToRepel['mast'] and $card['dost'] > $cardToRepel['dost']) {
                    // если у отбивающегося есть карта с такой же мастью и большим достоинством, то он отбивается
                    $_SESSION['gameBoard'][] = $card;
                    echo '<pre>', $GLOBALS['dost'][$card['dost']], $card['mast'], ' <-- ', $this->name, '</pre>';
                    $repeled = 1; // отбито
                    unset($this->cards[$key]);
                    break;
                }
            }
            //пробуем отбиться козырными
            if ($repeled !== 1) {
                if (!isset($this->trumps) || count($this->trumps) === 0) {
                    if (isset($this->trumps) && count($this->cards) !== 0) {
                        // поднимает все карты
                        $this->cards = array_merge($this->cards, $_SESSION['gameBoard']);
                        $this->skipStep = 1;
                        echo '<pre>', $this->name, ' <---- (';
                        foreach ($_SESSION['gameBoard'] as $card) {
                            echo $GLOBALS['dost'][$card['dost']], $card['mast'], ' ';
                        }
                        echo ') </pre>';
                        unset($_SESSION['gameBoard']);
                    } else {
                        Game::$stopAction = 1;
                    }
                } else {
                    $_SESSION['gameBoard'][] = $this->trumps[0];
                    echo '<pre>', $GLOBALS['dost'][$this->trumps[0]['dost']], $this->trumps[0]['mast'], ' <-- ', $this->name, '</pre>';
                    unset($this->trumps[0]);
                    if (count($this->trumps) !== 0) {
                        $this->trumps = array_values($this->trumps);
                    }
                    $repeled = 1;
                }
            }
        }
        $this->mergeWithTrumps();
        $this->sortPlayerCards();
    }
}
