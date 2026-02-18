<?php if($doctor_info->doctor_id!=NULL){
	$status="readonly";
	$disabled="readonly";
}else{
	$status="alt";
	$disabled='DNI';
}?>
<input type="hidden" name="doctor_id" value="<?php echo $doctor_info->doctor_id; ?>" id="doctor_id">

        <div class="row">
            <!-- Columna 1 -->
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <?php echo form_label(lang('Doctors.doctors_name') . ':', 'name', array('class' => 'required')); ?>
                    <?php echo form_input(array(
                        'name'  => 'name',
                        'id'    => 'name',
                        'class' => 'form-control',
                        'value' => $doctor_info->name
                    )); ?>
                </div>
            </div>

            <!-- Columna 2 -->
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <?php echo form_label(lang('Doctors.doctors_phone') . ':', 'phone_number', array('class' => 'required')); ?>
                    <?php echo form_input(array(
                        'name'  => 'phone_number',
                        'id'    => 'phone_number',
                        'class' => 'form-control',
                        'value' => $doctor_info->phone_number
                    )); ?>
                </div>
            </div>
        </div>
		
		        <div class="row">
            <!-- Columna 1 -->
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <?php echo form_label(lang('Doctors.doctors_speciality') . ':', 'speciality', array('class' => 'required')); ?>
                    <?php echo form_input(array(
                        'name'  => 'speciality',
                        'id'    => 'speciality',
                        'class' => 'form-control',
                        'value' => $doctor_info->speciality
                    )); ?>
                </div>
            </div>

            <!-- Columna 2 -->
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <?php echo form_label(lang('Doctors.doctors_address') . ':', 'address', array('class' => 'required')); ?>
                    <?php echo form_input(array(
                        'name'  => 'address',
                        'id'    => 'address',
                        'class' => 'form-control',
                        'value' => $doctor_info->address
                    )); ?>
                </div>
            </div>
        </div>
		

		        <div class="row">
							<div class="col-md-6 mb-3">
    <div class="form-group">
        <?php echo form_label(lang('Doctors.doctors_gender') . ':', 'gender', array('class' => 'required')); ?>
        <?php 
            // Opciones del select
            $gender_options = array(
                ''        => '-- Seleccione --',
                '1'    => 'Masculino',
                '2'  => 'Femenino'
            );

            // Campo select con valor seleccionado
            echo form_dropdown(
                'gender',                   // name
                $gender_options,           // opciones
                $doctor_info->gender,      // valor seleccionado
                'id="gender" class="form-control"' // atributos extra
            );
        ?>
    </div>
</div>
            <!-- Columna 2 -->
            <div class="col-md-6 mb-3">
                <div class="form-group">
                    <?php echo form_label(lang('Common.common_comments') . ':', 'comments', array('class' => 'required')); ?>
                   	<?php echo form_textarea(array(
		'name'=>'comments',
		'id'=>'comments',
		'value'=>$doctor_info->comments,
		'rows'=>'5',
		'cols'=>'19')		
	);?>
                </div>
            </div>
        </div>







