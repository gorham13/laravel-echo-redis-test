<template>
    <div class="container" style="height: 90vh">
        <h1 v-if="canClick == -1">Expecting opponent</h1>
        <h1 v-if="canClick == 0">Opponent`s move</h1>
        <h1 v-if="canClick == 1">Your move</h1>
        <div class="d-flex flex-wrap justify-content-center align-items-center border" style="height: 100%; overflow: auto">
            <div :key="i" v-for="i in x">
                <div class="border game-button" :ref="'point:'+i+','+j" :key="j" v-for="j in y" 
                    @click="onClick(i, j)">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                x: 24,
                y: 24,
                color: 'red',
                points: [],
                token: '',
                canClick: -1,
                userName: null
            }
        },
        created(){
            this.color = 'rgba(' + Math.round(Math.random()*255) + ',' + 
                                Math.round(Math.random()*255) + ',' + 
                                Math.round(Math.random()*255) + ',' + 
                                (Math.round(Math.random()*10)/10 + 0.3) + ')';

            this.userName = localStorage.getItem('five-in-row-player');
            if(!this.userName){
                localStorage.setItem('five-in-row-player', this.randomStr());
                this.userName = localStorage.getItem('five-in-row-player');
            }

            this.token = window.location.pathname.split( '/' ).pop();
            Echo.channel(this.token)
            .listen('TestEvent', (e) => {
                console.log('testEvent');
                console.log(e.userName);
                if(e.userName == this.userName){
                    this.canClick = 1;
                } else {
                    this.canClick = 0;
                }
            })
            .listen('MoveDone', (e) => {
                this.$refs['point:' + e.x + ',' + e.y][0].style.backgroundColor = e.color;
                this.points[e.x-1][e.y-1] = 0;
                this.canClick = 1;
            })
            .listen('Losed', (e) => {
                console.log('Losed');
                this.$swal(
                    'Good job!',
                    'You are loser! And now go and do your work!',
                    'error'
                )
            });
        },
        mounted() {
            let data = this;
            for(var i = 0; i < data.x; i++){
                data.points.push([]);
                for(var j = 0; j < data.y; j++){
                    data.points[i].push(-1);
                }
            }
            axios.post('/api/startup', {token: data.token, userName: data.userName})
                .then(function(res){
                    if(res.data){
                        for(var item of res.data){
                            if(item.user == data.userName){
                                data.points[item.x - 1][item.y - 1] = 1;
                                data.color = item.color;
                            } else {
                                data.points[item.x - 1][item.y - 1] = 0;
                            }
                            data.$refs['point:' + item.x + ',' + item.y][0].style.backgroundColor = item.color;
                        }
                        let lastMove = res.data.pop();
                        if(lastMove.user == data.userName){
                            data.canClick = 1;
                        } else { data.canClick = 0; }
                    }
                });
        },
        methods: {
            onClick(x, y){
                if(this.canClick == 1){
                    if(this.points[x-1][y-1] == -1){
                        this.$refs['point:' + x + ',' + y][0].style.backgroundColor = this.color;
                        this.points[x-1][y-1] = 1;
                        this.checkWinner(x-1, y-1);
                        axios.post('/api/moveDone', {token: this.token, x: x, y: y, color: this.color, user: this.userName}).then(function(){});
                        this.canClick = 0;
                    }
                }
            },
            checkWinner(x, y){
                if(this.diagonalLoop(x, y)||this.verticalLoop(y)||this.horizontalLoop(x)||this.invertDiagonalLoop(x, y)){
                    axios.post('/api/win', {token: this.token}).then(function(){});
                    this.$swal(
                        'Good job!',
                        'You are winner! And now go and do your work!',
                        'success'
                    )
                }
            },
            diagonalLoop(x, y){
                let diagonalStartPoint = {x: (x + y), y: 0};
                let count = 0
                while((diagonalStartPoint.x > 0)&&(diagonalStartPoint.y < this.y)){
                    if(this.points[diagonalStartPoint.x][diagonalStartPoint.y] == 1){
                        count++;
                    } else {
                        count*=0;
                    }
                    if(count == 5){
                        return 1;
                    }
                    diagonalStartPoint.x--;
                    diagonalStartPoint.y++;
                }
                return 0;
            },
            invertDiagonalLoop(x, y){
                let diagonalStartPoint = {x: 0, y: 0};
                if(x > y){
                    diagonalStartPoint = {x: (x - y), y: 0};
                } else {
                    diagonalStartPoint = {x: 0, y: (y - x)};
                }
                let count = 0
                while((diagonalStartPoint.x < this.x)&&(diagonalStartPoint.y < this.y)){
                    if(this.points[diagonalStartPoint.x][diagonalStartPoint.y] == 1){
                        count++;
                    } else {
                        count*=0;
                    }
                    if(count == 5){
                        return 1;
                    }
                    diagonalStartPoint.x++;
                    diagonalStartPoint.y++;
                }
                return 0;
            },
            verticalLoop(y){
                let count = 0;
                let x = 0;
                while(x < this.x)
                {
                    if(this.points[x][y] == 1){
                        count++;
                    } else {
                        count*=0;
                    }
                    if(count == 5){
                        return 1;
                    }
                    x++;
                }
                return 0;
            },
            horizontalLoop(x){
                let count = 0;
                let y = 0;
                while(y < this.y)
                {
                    if(this.points[x][y] == 1){
                        count++;
                    } else {
                        count*=0;
                    }
                    if(count == 5){
                        return 1;
                    }
                    y++;
                }
            },
            randomStr() {
                let text = "";
                let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

                for (let i = 0; i < 15; i++)
                    text += possible.charAt(Math.floor(Math.random() * possible.length));
                return text;
            }
        }
    }
</script>
<style>
    .game-button{
        width: 25px; 
        height: 25px;
        margin: 1px;
    }
</style>
