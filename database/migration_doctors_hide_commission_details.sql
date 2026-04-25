-- Migration para ocultar el detalle de comisiones en el portal del doctor

ALTER TABLE dom_doctors
  ADD COLUMN hide_commission_details tinyint(1) NOT NULL DEFAULT 0
  COMMENT '1=oculta el detalle de comisiones en el portal del doctor';
