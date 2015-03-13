<?php
/* -----------------------------------------------

Brian Suda
brian@suda.co.uk
2002/06/02

Timer Class
version 1.0

Functions:
 void timer()
 int  gettimer()

Variables:
 timer_clock

Description:
when you create a new timer class it stores the
current microtime in the variable timer_clock. 
The function gettimer() will return the elapsed
time from the creation of the variable to the
current time in microtime.

Usage:
  $time = new timer(); 
  // do something
  $currtime = $time->gettimer();
  echo "the operation took $currtime";

----------------------------------------------- */

  class timer { 
    var $timer_clock;


    function timer() { 
      //construtor to start the timer when the object is created

      list($usec, $sec) = explode(" ",microtime());
      $this->timer_clock=((float)$usec + (float)$sec);

    }

    function gettimer(){
     // returns the current time elapsed since the object was created

     list($usec, $sec) = explode(" ",microtime());
     $temp = ((float)$usec + (float)$sec)-$this->timer_clock;
     return $temp;

    }

  }

?>