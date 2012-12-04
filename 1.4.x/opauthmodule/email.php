
 
  <script>
  $(document).ready(function(){
     $("#commentForm").validate();
  });
  </script>
  


 <form  id="commentForm" method="post" action="" class="std">
 <fieldset>

 <h3>Finir votre inscription</h3>

  
    <p class="required text">
     <label for="cemail">E-Mail <sup>*</sup></label><span>
<input id="cemail" name="email_opthmodule" size="25"  class="required email text" /></span>
</p>
	 <?php if($_GET['provider']=="Twitter"){?>
	    <p class="required text">
	 <label for="cemail">First name  <sup>*</sup></label><span>
<input id="firstname" name="firstname" size="25"  class="required text" /> </span>
	 </p>
	    <p class="required text">
	 <label for="cemail">Last name  <sup>*</sup></label><span>
<input id="lastname" name="lastname" size="25"  class="required text" /> </span>
	 </p>
	 <?php }else{?>
	 <input type="hidden" value="<?=$_GET['firstname']?>" name="firstname"/>
  <input type="hidden" value="<?=$_GET['lastname']?>" name="lastname"/>
  <?php } ?>
   </p>
  <input type="hidden" value="<?=$_GET['gender']?>" name="gender"/>
  
  <input type="hidden" value="<?=$_GET['provider']?>" name="provider"/>
  <input type="hidden" value="<?=$_GET['idu']?>" name="id"/>
   <p>
     <input class="submit" type="submit" value="Submit"/>
   </p>
 </fieldset>
 </form>
