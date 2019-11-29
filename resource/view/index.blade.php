<!--jquery需要引入的文件-->
<script src="/js/jquery-3.2.1.js"></script>

<!--ajax提交表单需要引入jquery.form.js-->
<script type="text/javascript" src="/js/jquery.form.js"></script>
<div style="display:flex;justify-content: center; align-items:center; width: 100%;height: 100%;border: 1px solid white;text-align:center;">
    <div>
        <span style="line-height: 50px">ExcelToMysql</span>
        <form action="" method="post" enctype="multipart/form-data" id="ajaxForm">
            <input type="text" name="frameId" id="frameId">
            <input type="file" name="excel" required>
            <input type="submit" id="ajaxSubmit">
        </form>
    </div>
</div>
<script>
    $(function () {
        //给id为ajaxSubmit的按钮提交表单
        $("#ajaxSubmit").on("click",function () {
            //alert(1);
            $("#ajaxForm").ajaxSubmit({
                beforeSubmit:function () {
                    // alert("我在提交表单之前被调用！");
                },
                success:function (data) {
                    //alert("我在提交表单成功之后被调用");
                    handle(data);
                }
            });
            return false;
        });
    });
    //处理返回数据
    function handle(data){
        if(data.status == 200){
            alert(data.message);
            //处理逻辑
        }else{
            alert(data.message);
            //处理逻辑
        }
    }
</script>
<script>
    var ws = new WebSocket("ws://119.23.225.253:9502");
    //readyState属性返回实例对象的当前状态，共有四种。
    //CONNECTING：值为0，表示正在连接。
    //OPEN：值为1，表示连接成功，可以通信了。
    //CLOSING：值为2，表示连接正在关闭。
    //CLOSED：值为3，表示连接已经关闭，或者打开连接失败
    //例如：if (ws.readyState == WebSocket.CONNECTING) { }

    //【用于指定连接成功后的回调函数】
    ws.onopen = function (event) {
        // ws.send(JSON.stringify({'from':'client','act':'open'}));
    };
    //ws.addEventListener('open', function (event) {
    //    ws.send('Hello Server!');
    //};

    //【用于指定收到服务器数据后的回调函数】
    //【服务器数据有可能是文本，也有可能是二进制数据，需要判断】
    ws.onmessage = function (event) {
        if (typeof event.data === String) {
            console.log("Received data string");
        }

        if (event.data instanceof ArrayBuffer) {
            var buffer = event.data;
            console.log("Received arraybuffer");
        }
        data = JSON.parse(event.data);
        switch (data.act) {
            case 'open':
                $('#frameId').val(data.data);
                break;
            case 'notice':
                alert(data.data)
                break;
        }
    };
</script>