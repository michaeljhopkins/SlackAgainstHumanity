<?php namespace Hopkins\SlackAgainstHumanity\Models;

use Hopkins\GamesBase\Models\Player;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * Hopkins\SlackAgainstHumanity\Models\Card
 *
 * @property integer $id
 * @property string $text
 * @property string $color
 * @property integer $dealt
 * @property integer $played
 * @property integer $in_play
 * @property integer $player_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Player $player
 * @property-read \Illuminate\Database\Eloquent\Collection|\$related[] $morphedByMany
 * @method static Builder|Card whereId($value)
 * @method static Builder|Card whereText($value)
 * @method static Builder|Card whereColor($value)
 * @method static Builder|Card whereDealt($value)
 * @method static Builder|Card wherePlayed($value)
 * @method static Builder|Card whereInPlay($value)
 * @method static Builder|Card wherePlayerId($value)
 * @method static Builder|Card whereCreatedAt($value)
 * @method static Builder|Card whereUpdatedAt($value)
 * @method static Card randomWhites()
 * @method static Card randomNewBlack()
 */
class Card extends Model
{
    protected $guarded = ['id'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function scopeRandomWhites($query)
    {
        /** @var /Hopkins/SlackAgainstHumanity/Models/Card| $q */
        $q = $query;

        return $q->whereColor('white')->whereDealt(0)->orderBy(DB::raw('RAND()'));
    }
    public function scopeRandomNewBlack($query)
    {
        return $query->whereColor('black')->whereDealt(0)->orderBy(DB::raw('RAND()'));
    }
}
