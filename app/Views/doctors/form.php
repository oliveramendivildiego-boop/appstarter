<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'doctors']) ?>
<script type='text/javascript'>

//validation and submit handling
$(document).ready(function()
{
  ///////////////////////////validar formulario
  
$("#customer_form").validate({
    rules: {
      name: { required: true, minlength: 2 },
	  gender: { required: true }
    },
    messages: {
      name: {
        required: "Por favor ingrese su nombre(s) y apellido(s)",
        minlength: "El nombre debe tener al menos 2 caracteres"
      },
	  gender: {
      required: "Seleccione su género"
      },
    },
    errorClass: "is-invalid text-danger small",
    validClass: "is-valid",
    errorElement: "div",
    highlight: function(element) {
      $(element).addClass("is-invalid").removeClass("is-valid");
    },
    unhighlight: function(element) {
      $(element).removeClass("is-invalid").addClass("is-valid");
    },
    errorPlacement: function(error, element) {
      if (element.parent(".input-group").length) {
        error.insertAfter(element.parent());
      } else {
        error.insertAfter(element);
      }
    }
  });
  
});
</script>
<?php
$dmItems = $doctor_info->doctor_id
    ? [['label' => lang('Module.module_doctors'), 'url' => site_url($controller_name ?? 'doctors')], ['label' => $doctor_info->name ?? '', 'url' => site_url(($controller_name ?? 'doctors') . '/view/' . $doctor_info->doctor_id)]]
    : [['label' => lang('Module.module_doctors'), 'url' => site_url($controller_name ?? 'doctors')], ['label' => lang('Doctors.doctors_new'), 'url' => null]];
$dmRight = ($doctor_info->doctor_id ?? 0)
    ? anchor(($controller_name ?? 'doctors') . '/delete/' . ($doctor_info->doctor_id ?? 0), lang('Common.common_delete'), ['class' => 'btn btn-danger', 'title' => lang('Common.common_delete')])
    : '';
?>
<?= view('partial/breadcrumb_nav', ['items' => $dmItems, 'right' => $dmRight]) ?>
<?php
echo form_open('doctors/save/'.$doctor_info->doctor_id,array('id'=>'customer_form','data-async'=>'1'));
?>
<fieldset id="customer_basic_info">

<?= view('doctors/form_basic_info') ?>

</fieldset>
<div class="submit_content">
<?php
echo form_submit(array(
	'name'=>'submit',
	'id'=>'submit',
	'value'=>lang('Common.common_submit'),
	'class'=>'btn btn-primary')
);
?>
</div>
<?php 
echo form_close();
?>
<?= view('partial/footer') ?>