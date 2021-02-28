<?php
$faces = [6, 7, 8, 9, 10, 11, 12, 13, 14]; // достоинства карт
class Cards
{
    public $deck;

    public function __construct(int $rand)
    {
        global $faces;
        $suits = ['♠', '♥', '♣', '♦']; // масти карт
        $deck = [];
        // создадим массив карт (колоду) соединением мастей и достоинств карт
        foreach ($suits as $suit) {
            foreach ($faces as $face) {
                $deck[] = ['dost' => $face, 'mast' => $suit];
            }
        }
        /*
        Перед началом игры колода сортируется заданным случайным числом:
        выполняется 1000 итераций, в каждой итерации из колоды берется карта с порядковым номером n
        n = (random + iterator * 2) mod 36
        (где random - случайное переданное число, а iterator - номер итерации сортировки 0…999)
        и перемещается в начало колоды
        */
        for ($i = 0; $i < 1000; $i++) {
            $n = ($rand + $i * 2) % 36;
            $tmp = $deck[$n];
            unset($deck[$n]);
            array_unshift($deck, $tmp);
            unset($tmp);
        }
        $this->deck = $deck;
        unset($deck);
    }
}
