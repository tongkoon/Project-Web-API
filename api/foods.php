<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request; 

//Show All Foods
$app->get('/foods', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['dbconn'];
    $sql = "SELECT * FROM `foods`";
    $result = $conn->query($sql);
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data, $row);
    }
    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('Content-Type', 'application/json');
});

// Search by tid -> *
$app->get('/foods/tid/{tid}', function (Request $request, Response $response, array $args) {
    $tid = $args["tid"];
    
    $conn = $GLOBALS["dbconn"];
    $sql = "select foods.fid,foods.name,foods.price,foods.image
            from foods,food_type 
            where foods.fid = food_type.fid 
            and food_type.tid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$tid);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data,$row);
    }

    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

$app->get('/foods/name/{keyword}', function (Request $request, Response $response, array $args) {
    $keyword ='%'.$args["keyword"].'%';
    
    $conn = $GLOBALS["dbconn"];
    $sql = "select * from foods where name like ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s",$keyword);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = array();
    while($row = $result->fetch_assoc()){
        array_push($data,$row);
    }

    $json = json_encode($data);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// insert
$app->post('/foods/insert', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "insert into foods (name,price,image) 
            values(?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis",$bodyArr["name"],$bodyArr["price"],$bodyArr["image"]);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    if($affected_rows>0){
        $last_id = $conn->insert_id;
        $sql = "insert into food_type (fid,tid) 
            values(?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii",$last_id,$bodyArr["tid"]);
        $stmt->execute();
    }
    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// delete
$app->post('/foods/delete', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];

    $sql = "delete from foods where fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$bodyArr["fid"]);
    $stmt->execute();

    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});

// update
$app->post('/foods/update', function (Request $request, Response $response, array $args) {
    $body = $request->getBody();
    $bodyArr = json_decode($body,true);

    $conn = $GLOBALS["dbconn"];
    $sql = "update food_type set tid = ? where fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii",$bodyArr["tid"],$bodyArr["fid"]);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;

    $sql = "update foods set name = ?,price = ?,image = ?
            where fid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi",$bodyArr["name"],$bodyArr["price"],$bodyArr["image"],$bodyArr["fid"]);
    $stmt->execute();

    $value = array("status"=>'success');
    $json = json_encode($value);
    $response->getBody()->write($json);
    return $response->withHeader('content-type','application/json');
});
?>