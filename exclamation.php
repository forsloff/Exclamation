<?php



    namespace Exclamation ;

    /*
            ..      .                                  ..                                                  s       .                              
            x88f` `..x88. .>                        x .d88"                                                  :8      @88>                            
        :8888   xf`*8888%     uL   ..              5888R                  ..    .     :                   .88      %8P          u.      u.    u.   
        :8888f .888  `"`     .@88b  @88R       .    '888R         u      .888: x888  x888.        u       :888ooo    .     ...ue888b   x@88k u@88c. 
        88888' X8888. >"8x  '"Y888k/"*P   .udR88N    888R      us888u.  ~`8888~'888X`?888f`    us888u.  -*8888888  .@88u   888R Y888r ^"8888""8888" 
        88888  ?88888< 888>    Y888L     <888'888k   888R   .@88 "8888"   X888  888X '888>  .@88 "8888"   8888    ''888E`  888R I888>   8888  888R  
        88888   "88888 "8%      8888     9888 'Y"    888R   9888  9888    X888  888X '888>  9888  9888    8888      888E   888R I888>   8888  888R  
        88888 '  `8888>         `888N    9888        888R   9888  9888    X888  888X '888>  9888  9888    8888      888E   888R I888>   8888  888R  
        `8888> %  X88!       .u./"888&   9888        888R   9888  9888    X888  888X '888>  9888  9888   .8888Lu=   888E  u8888cJ888    8888  888R  
        `888X  `~""`   :   d888" Y888*" ?8888u../  .888B . 9888  9888   "*88%""*88" '888!` 9888  9888   ^%888*     888&   "*888*P"    "*88*" 8888" 
            "88k.      .~    ` "Y   Y"     "8888P'   ^*888%  "888*""888"    `~    "    `"`   "888*""888"    'Y"      R888"    'Y"         ""   'Y"   
            `""*==~~`                      "P'       "%     ^Y"   ^Y'                       ^Y"   ^Y'               ""                             
        
    */                                                                                                                                          
                                                                                                                                             


    trait Response {

        public $statuses = [
            'ok' => 200,'created' => 201, 'no_content' => 204,'moved_permanently' => 301,
            'not_modified' => 304, 'bad_request' => 400, 'unauthorized' => 401, 'forbidden' => 403,
            'not_found' => 404, 'unprocessable_entity' => 422, 'internal_server_error' => 500];

        public function not_found() {
            echo "not_found";
            exit;
        }

        public function __call($method, $args) {
            if(array_key_exists($method, $this->statuses )) {

                echo $args[0];
                exit;
            }
        }
    }

    class Request {

        public $request;
        
        public function __construct($matches = null) {
            $this->request = $_SERVER;
        }

        public function verb() {
            return strtolower($this->request['REQUEST_METHOD']);
        }

        public function path() {
            return $this->request['REQUEST_URI'];
        }
       
    }

    class Context {

        use \Exclamation\Response;

        public $request = null;
        public $params = [];
        public $settings = [];
    
        public function __construct($matches=[], $settings) {
            $this->request = new \Exclamation\Request();
            $this->params = $matches;
            $this->settings = $settings;
        }

        public function setting($key) {
            return $this->settings[$key];
        }
        
        public function params($key) {
            return $this->params[$key];
        }

        public function process($action){
            $action($this);
        }
        
    }

    class Application {

        use \Exclamation\Response;

        public $paths = [];
        public $methods = ['get', 'post', 'patch', 'delete', 'put', 'head', 'link', 'unlink'];
        public $settings = [];

        public function __construct() {
            foreach($this->methods as $method) $paths[$method] = [];
        }
        //
        // $this->get('/google_chrome_only', ['user_agent' => 'google_chrome'], function($i){
        //   ---
        // })

        public function __call($method, $arguments) {
            if(in_array($method, $this->methods)) {
                $endpoint = new \stdClass();
                $action = array_pop($arguments);
                if( !is_callable($action) ) {
                    echo "A action must be supplies in the form of a Closure or a Class";
                }
                $endpoint->action = $action;
                $endpoint->regex  = $this->covert_path_representation_to_reguler_expression($arguments[0]);
                $endpoint->method = $method;

                if( isset($arguments[1]) && is_array($arguments[1]) ){
                    foreach($arguments[1] as $key => $value){
                        $endpoint->$key = $value;
                    }
                }
                $this->paths[$method][$arguments[0]] = $endpoint;
            }
        }

        public function run(){
            $request = new \Exclamation\Request();
            foreach($this->paths[ $request->verb() ] as $path){
                preg_match($path->regex, $request->path(), $matches);
                if($matches){
                    $context = new \Exclamation\Context($matches, $this->settings);
                    return $context->process($path->action);
                }
            }
            $this->not_found();
        }

        public function setting($key, $value) {
            $this->settings[$key] = $value;
        }

        public function enable($key) {
            $this->settings[$key] = true;
        }

        public function disable($key) {
            $this->settings[$key] = false;
        }

        public function covert_path_representation_to_reguler_expression($path) {
            $parts = explode('/',$path);
            $parts = array_filter($parts);

            foreach($parts as $index => $part) {
                if( preg_match('/^:[a-zA-Z_]+$/', $part)) {
                    $parts[$index] = preg_replace('/^:([a-zA-z_]+)$/', '(\w+)', $part);
                }
            }
            $expression = "/^\/" . join("\/", $parts) . "$/";
            return $expression;
        }
    }
    
?>