<?php

namespace App\Http\Controllers\Auth;

use Auth;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Contracts\Auth\Authenticatable;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Crea la autenticación y extración de roles del ldap.
     *
     * @return void
     */

    public function login(Request $request){

      $username  = $request->input('username');
      $password  = $request->input('password');;

      $connection = ldap_connect(env("LDAP_HOST"), env("LDAP_PORT")) or die ("No ha sido posible conectarse");
      ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
      ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

      if ($connection) {
         if (@ldap_bind($connection, env("USERDN"), env("PASSWORD_USERDN"))) {
            $getcn = ldap_search($connection, env("BASE_LDAP"), "uid=".$username);
            if (ldap_count_entries($connection, $getcn) == 1) {
               $entry_user = ldap_first_entry( $connection, $getcn );
               $dn_user = ldap_get_dn( $connection, $entry_user );

               if (@ldap_bind($connection, $dn_user, $password)) {
	          $user = new User;

	          $entry = ldap_first_entry($connection, $getcn);
		  $info = ldap_get_attributes($connection, $entry);

		  for ($i=0; $i < $info["count"]; $i++) {
		    $values = ldap_get_values($connection, $entry, $info[$i]);
		    $user->{$info[$i]} = $values[0];
		  }

		  @ldap_bind($connection, env("USERDN"), env("PASSWORD_USERDN"));
                  $getroles = ldap_search($connection, env("BASE_ROLES"), str_replace("%s" , $username, env("FILTER_ROLES")), array("cn"));
		  
		  $entry = ldap_first_entry($connection, $getroles);
		  if ($entry){
                    do {
  		       $values = ldap_get_values($connection, $entry, "cn");
 		       for ($i=0; $i < $values["count"]; $i++) {
		          $roles[] = $values[$i];
 		       }
                    } while ($entry = ldap_next_entry($connection, $entry));

		    $user->rol = $roles;
		  }else{
		    return view('auth.login')->with(['error' => 'Acceso denegado']);
		  }

		  Auth::login($user);
		  return view('home'); 

 	       }else{
		  return view('auth.login')->with(['error' => 'Usuario o Contraseña incorrecto']);
	       }
	    }
        }else{
	  return view('auth.login')->with(['error' => 'Usuario o contraseña incorrecta del administrador']);
	}
    }
    ldap_close($conexion);
  }
}
?>
