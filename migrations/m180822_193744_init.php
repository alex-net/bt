<?php
// zIJXNMy94WUr6Vb
use yii\db\Migration;


/**
 * Class m180822_193744_init
 */
class m180822_193744_init extends Migration
{
    /**
     * {@inheritdoc}
     */
    /*public function safeUp()
    {

    }*/

    /**
     * {@inheritdoc}
     */
    /*public function safeDown()
    {
        echo "m180822_193744_init cannot be reverted.\n";

        return false;
    }*/

    
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        // таблица сессий .. 
        $this->createTable('sessi',[
            'id'=>$this->string(40)->comment('Ключик сесии'),
            'expire'=>$this->integer()->comment('Окончание действия'),
            'data'=>$this->binary()->comment('Данные сессии'),
        ]);
        $this->createIndex('sessi_expir','sessi','expire');
        $this->addPrimaryKey('primkeysessi','sessi',['id']);

        // таблица юзера .. .
        $this->createTable('bt_user',[
            'id'=>$this->primaryKey()->comment('Ключик юзера'),
            'mail'=>$this->string(40)->comment('Было юзера'),
            'name'=>$this->string(40)->comment('Имя'),
            'status'=>$this->boolean()->defaultValue(true)->comment('Статус пользователя'),
            'role'=>$this->string(10)->defaultValue('owner')->comment('Роль юзера'),
            'pass'=>$this->string(60)->comment('пароль'),
            'created'=>$this->dateTime()->defaultExpression('now()')->comment('Дата создания пользовтеля'),
            'lastlogin'=>$this->dateTime()->comment('Дата первого захода'),
            'token'=>$this->string(60)->comment('Ключик доступа'),
        ]);
        $this->createIndex('btusermail','bt_user','mail',true);
        $this->createIndex('btusertoken','bt_user','token',true);
        //$sec= new \yii\base\Security();
        $pass=Yii::$app->security->generateRandomString(15);
        $hash=\Yii::$app->security->generatePasswordHash($pass);
        $this->insert('bt_user',[
            'mail'=>'admin@test-adminsky.com',
            'name'=>'admin',
            'role'=>\app\models\BtUser::ROLE_SADMIN,
            'status'=>true,
            'pass'=>$hash,
            'token'=>\Yii::$app->security->generateRandomString(60),
        ]);
        echo 'Создан юзер  mail= admin@test-adminsky.com  пароль '.$pass."\n";

        // Таблица майнеров . 
        $this->db->createCommand('create table bt_miner (
            id serial primary key,
            ip inet not null,port integer not null check(port>0),
            login varchar(20),
            pass varchar(35), 
            status boolean default true,
            "owner" integer references bt_user(id) on delete cascade,
            timeupd timestamp,
            pools varchar(120)[],
            "interval" integer,
            updating boolean default false,
            badupds integer default 0,
            weight integer default 0
        )')->execute();
        // добавляем ключики .. 
        $this->createIndex('btminerid','bt_miner','minerid',true);
        $this->createIndex('btminerip','bt_miner','ip',true);


        /** таблица истории майнеров */
        $this->db->createCommand('create table bt_miner_history(
            id integer not null references bt_miner (id) on delete cascade on update cascade not null,
            Elapsed integer,
            dt timestamp not null default now(),
            ghs_5s numeric(10,2),
            ghs_av numeric(10,2),
            fans integer[],
            temp integer[][],
            freq_avg numeric(10,2)[],
            chain_rateideal numeric(10,2)[],
            chain_acn integer[],
            chain_rate integer[]
        )');
        /*
        Elapsed = int    
GHS 5s = float    
GHS av = float 
fans[] = int
temp[][]= int
freq_avg[] = decimal(7,2)
chain_rateideal[] = decimal(7.2)
chain_acn[] = int
chain_rate[] = float

        */
    }

    public function down()
    {
       // echo "m180822_193744_init cannot be reverted.\n";
        $this->dropTable('sessi');
        $this->dropTable('bt_user');
   //     return false;
    }
    
}
