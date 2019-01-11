#!/bin/bash
single_exec(){
    r=`ps aux | grep "$1" | grep -v grep`
    if [ -z "$r" ] ; then
        echo 'miss'
        #DATE=`date +%Y%m%d%H%M`
        #echo $DATE >> /tmp/st.log
        `/usr/bin/php $1`
    else
        pid=`echo $r | awk '{print $2}'`
        cpu=`echo $r | awk '{print $3}'`
        mem=`echo $r | awk '{print $4}'`
        stat=`echo $r | awk '{print $8}'`
        stime=`echo $r | awk '{print $9}'`
        stime=`date -d "$stime" "+%s"`
        now=`date '+%s'`
        etime=$[now-stime]
        if [ "$etime" -gt 300 ] && [ "$stat" = "S" ]; then
            `kill "$pid"`
        fi

        echo 'hit'
        exit 0
    fi
}

step=1

for((i=0;i<60;i=$[i+step] ))
do
    single_exec '/mnt/www/rbac.fruitday.com/index.php cron/tmscode run'
    sleep $step
done