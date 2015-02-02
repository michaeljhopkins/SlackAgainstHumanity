<?php namespace Hopkins\SlackAgainstHumanity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * Hopkins\SlackAgainstHumanity\Models\Player
 *
 * @property integer $id
 * @property string $user_name
 * @property integer $cah
 * @property integer $played
 * @property integer $num_cards
 * @property integer $is_judge
 * @property integer $idle
 * @property-read \Illuminate\Database\Eloquent\Collection|\Hopkins\SlackAgainstHumanity\Models\Card[] $cards
 * @property-read \Illuminate\Database\Eloquent\Collection|\$related[] $morphedByMany
 * @method static Builder|Player whereId($value)
 * @method static Builder|Player whereUsername($value)
 * @method static Builder|Player whereCah($value)
 * @method static Builder|Player wherePlayed($value)
 * @method static Builder|Player whereNumCards($value)
 * @method static Builder|Player whereIsJudge($value)
 * @method static Builder|Player whereIdle($value)
 */

class Player extends Model{
    protected $table = 'users';
    protected $fillable = ['user_name','cah','idle','is_judge','played','num_cards'];

    public function cards(){
        return $this->hasMany(Card::class);
    }
}