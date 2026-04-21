from typing import Any

from flask import Blueprint, flash, redirect, render_template, request, url_for

from adms_wrapper.models.db_queries_models import (
    add_shift_template,
    delete_shift_template,
    get_default_shift,
    get_setting,
    get_shift_templates,
    set_default_shift,
    set_setting,
)

router = Blueprint('settings',__name__)

@router.route("/shift_templates", methods=["GET", "POST"])
def shift_templates() -> Any:
    """Manage shift templates."""
    if request.method == "POST":
        delete_shift_name = request.form.get("delete_shift_name")
        if delete_shift_name:
            delete_shift_template(delete_shift_name)
            flash(f"Shift template deleted: {delete_shift_name}", "success")
            return redirect(url_for("shift_templates"))

        shift_name = request.form.get("shift_name")
        shift_start = request.form.get("shift_start")
        shift_end = request.form.get("shift_end")
        description = request.form.get("description", "")

        if shift_name and shift_start and shift_end:
            add_shift_template(shift_name, shift_start, shift_end, description)
            flash(f"Shift template added: {shift_name}", "success")
        else:
            flash("Shift name, start time, and end time are required.", "error")

        return redirect(url_for("shift_templates"))

    templates = get_shift_templates() or []
    return render_template("shift_templates.html", templates=templates)


@router.route("/settings", methods=["GET", "POST"])
def settings() -> Any:
    """Manage system settings like default shift."""
    if request.method == "POST":
        action = request.form.get("action")

        if action == "set_default_shift":
            default_shift = request.form.get("default_shift")

            if not default_shift:
                flash("Please select a default shift", "error")
                return redirect(url_for("settings.settings"))

            try:
                set_default_shift(default_shift)
                flash(f"Default shift set to '{default_shift}' successfully!", "success")
            except Exception as e:
                flash(f"Error setting default shift: {e!s}", "error")

        if action == "set_shift_settings":
            # Save shift-related numeric settings
            shift_cap_hours = request.form.get("shift_cap_hours")
            late_checkout_grace_minutes = request.form.get("late_checkout_grace_minutes")
            shift_cap_type = request.form.get("shift_cap_type")

            errors = False
            try:
                if shift_cap_hours is not None and str(shift_cap_hours).strip() != "":
                    int(shift_cap_hours)
                    set_setting("shift_cap_hours", str(shift_cap_hours), "Hours after shift end + grace period to consider no-checkout / shift capped")
            except Exception:
                flash("shift_cap_hours must be an integer", "error")
                errors = True

            # Note: early_checkin_minutes setting has been removed, early check-ins are now considered normal

            try:
                if late_checkout_grace_minutes is not None and str(late_checkout_grace_minutes).strip() != "":
                    int(late_checkout_grace_minutes)
                    set_setting("late_checkout_grace_minutes", str(late_checkout_grace_minutes), "Minutes after shift end before checkout is considered overtime")
            except Exception:
                flash("late_checkout_grace_minutes must be an integer", "error")
                errors = True

            # Set shift cap type - either "normal" or "zero"
            if shift_cap_type in ["normal", "zero"]:
                set_setting("shift_cap_type", shift_cap_type, "How to handle hours for shift-capped entries")
            else:
                set_setting("shift_cap_type", "normal", "How to handle hours for shift-capped entries")  # Default to normal if invalid

            if not errors:
                flash("Shift settings saved successfully", "success")

        return redirect(url_for("settings.settings"))

    # GET request - show settings form
    try:
        current_default_shift = get_default_shift()
        all_shifts = get_shift_templates()
        current_shift_cap = get_setting("shift_cap_hours") or "8"
        current_late_checkout_grace = get_setting("late_checkout_grace_minutes") or "15"
        current_shift_cap_type = get_setting("shift_cap_type") or "normal"
    except Exception as e:
        flash(f"Error loading settings: {e!s}", "error")
        current_default_shift = None
        all_shifts = []
        current_shift_cap = "8"
        current_late_checkout_grace = "15"
        current_shift_cap_type = "normal"

    return render_template(
        "settings.html",
        current_default_shift=current_default_shift,
        all_shifts=all_shifts,
        current_shift_cap=current_shift_cap,
        current_late_checkout_grace=current_late_checkout_grace,
        current_shift_cap_type=current_shift_cap_type,
    )
