import tempfile
from typing import Any

import pandas as pd
from flask import Blueprint, flash, redirect, render_template, request, send_file, url_for

from adms_wrapper.models.db_queries_models import (
    add_comprehensive_employee,
    add_device_branch_mapping,
    add_employee_designation_mapping,
    add_employee_name_mapping,
    add_shift_template,
    delete_comprehensive_employee,
    delete_device_branch_mapping,
    delete_shift_template,
    get_comprehensive_employee_data,
    get_default_shift,
    get_device_branch_mappings,
    get_employee_branch_mappings,
    get_employee_designation_mappings,
    get_employee_name_mappings,
    get_shift_templates,
    update_comprehensive_employee,
)

router = Blueprint("employee", __name__)


@router.route("/employee_management", methods=["GET", "POST"])
def employee_management() -> Any:
    """Comprehensive employee management."""
    if request.method == "POST":
        action = request.form.get("action", "add")

        if action == "delete":
            delete_emp_id = request.form.get("delete_employee_id")
            if delete_emp_id:
                try:
                    delete_comprehensive_employee(delete_emp_id)
                    flash(f"Employee data deleted: {delete_emp_id}", "success")
                except Exception as e:
                    flash(f"Error deleting employee {delete_emp_id}: {e!s}", "error")
                return redirect(url_for("employee_management"))

        elif action == "edit":
            edit_emp_id = request.form.get("edit_employee_id")
            employee_name = request.form.get("employee_name", "").strip()
            designation = request.form.get("designation", "").strip()
            branch_name = request.form.get("branch_name", "").strip()
            shift_name = request.form.get("shift_name", "").strip()

            if edit_emp_id:
                try:
                    update_comprehensive_employee(
                        edit_emp_id, employee_name if employee_name else None, designation if designation else None, branch_name if branch_name else None, shift_name if shift_name else None
                    )
                    flash(f"Employee data updated: {edit_emp_id}", "success")
                except ValueError as e:
                    flash(f"Error updating employee {edit_emp_id}: {e}", "error")
                except Exception as e:
                    flash(f"Unexpected error updating employee {edit_emp_id}: {e}", "error")
            else:
                flash("Employee ID is required for editing.", "error")
            return redirect(url_for("employee_management"))

        else:  # Default add/update action
            delete_emp_id = request.form.get("delete_employee_id")
            if delete_emp_id:
                delete_comprehensive_employee(delete_emp_id)
                flash(f"Employee data deleted: {delete_emp_id}", "success")
                return redirect(url_for("employee_management"))

            employee_id = request.form.get("employee_id")
            employee_name = request.form.get("employee_name", "")
            designation = request.form.get("designation", "")
            branch_name = request.form.get("branch_name", "")
            shift_name = request.form.get("shift_name", "")

            if employee_id:
                try:
                    add_comprehensive_employee(employee_id, employee_name, designation, branch_name, shift_name)
                    flash(f"Employee data updated: {employee_id}", "success")
                except ValueError as e:
                    # Known validation errors from lower layers (e.g., duplicate name)
                    flash(f"Error adding employee {employee_id}: {e!s}", "error")
                except Exception as e:
                    flash(f"Unexpected error adding employee {employee_id}: {e!s}", "error")
            else:
                flash("Employee ID is required.", "error")

            return redirect(url_for("employee_management"))

    employees = get_comprehensive_employee_data() or []
    all_branches = sorted({b["branch_name"] for b in get_device_branch_mappings() or []})
    all_designations = sorted({d["designation"] for d in get_employee_designation_mappings() or []})
    all_employee_names = sorted({n["employee_name"] for n in get_employee_name_mappings() or []})
    shift_templates = get_shift_templates() or []

    # Provide a fallback lookup of employee_id -> employee_name so templates can display names when missing
    try:
        name_mappings = get_employee_name_mappings() or []
        employee_id_to_name = {str(n.get("employee_id")): n.get("employee_name", "") for n in name_mappings if n.get("employee_id")}
    except Exception:
        employee_id_to_name = {}

    return render_template(
        "employee_management.html",
        employees=employees,
        all_branches=all_branches,
        all_designations=all_designations,
        all_employee_names=all_employee_names,
        shift_templates=shift_templates,
        employee_id_to_name=employee_id_to_name,
    )


@router.route("/unified_management", methods=["GET", "POST"])
def unified_management() -> Any:
    """Unified management interface for all system entities."""
    if request.method == "POST":
        action = request.form.get("action")

        if action == "employee":
            # Handle employee management
            delete_emp_id = request.form.get("delete_employee_id")
            if delete_emp_id:
                try:
                    delete_comprehensive_employee(delete_emp_id)
                    flash(f"Employee deleted: {delete_emp_id}", "success")
                except Exception as e:
                    flash(f"Error deleting employee {delete_emp_id}: {e!s}", "error")
                return redirect(url_for("employee.unified_management"))

            edit_emp_id = request.form.get("edit_employee_id")
            if edit_emp_id:
                # Handle edit action
                employee_name = request.form.get("employee_name", "").strip()
                designation = request.form.get("designation", "").strip()
                branch_name = request.form.get("branch_name", "").strip()
                shift_name = request.form.get("shift_name", "").strip()

                try:
                    update_comprehensive_employee(
                        edit_emp_id, employee_name if employee_name else None, designation if designation else None, branch_name if branch_name else None, shift_name if shift_name else None
                    )
                    flash(f"Employee data updated: {edit_emp_id}", "success")
                except ValueError as e:
                    flash(f"Error updating employee {edit_emp_id}: {e!s}", "error")
                except Exception as e:
                    flash(f"Unexpected error updating employee {edit_emp_id}: {e!s}", "error")
            else:
                # Handle add action
                employee_id = request.form.get("employee_id")
                employee_name = request.form.get("employee_name", "")
                designation = request.form.get("designation", "")
                branch_name = request.form.get("branch_name", "")
                shift_name = request.form.get("shift_name", "")

                if employee_id:
                    try:
                        add_comprehensive_employee(employee_id, employee_name, designation, branch_name, shift_name)
                        flash(f"Employee data updated: {employee_id}", "success")
                    except ValueError as e:
                        flash(f"Error adding employee {employee_id}: {e!s}", "error")
                    except Exception as e:
                        flash(f"Unexpected error adding employee {employee_id}: {e!s}", "error")
                else:
                    flash("Employee ID is required.", "error")

        elif action == "shift_template":
            # Handle shift template management
            delete_shift_name = request.form.get("delete_shift_name")
            if delete_shift_name:
                delete_shift_template(delete_shift_name)
                flash(f"Shift template deleted: {delete_shift_name}", "success")
                return redirect(url_for("employee.unified_management"))

            shift_name = request.form.get("shift_name")
            shift_start = request.form.get("shift_start")
            shift_end = request.form.get("shift_end")
            description = request.form.get("description", "")

            if shift_name and shift_start and shift_end:
                add_shift_template(shift_name, shift_start, shift_end, description)
                flash(f"Shift template added: {shift_name}", "success")
            else:
                flash("Shift name, start time, and end time are required.", "error")

        elif action == "device_branch":
            # Handle device branch mapping
            delete_serial = request.form.get("delete_serial_number")
            if delete_serial:
                delete_device_branch_mapping(delete_serial)
                flash(f"Device mapping deleted: {delete_serial}", "success")
                return redirect(url_for("employee.unified_management"))

            serial_number = request.form.get("serial_number")
            branch_name = request.form.get("branch_name")

            if serial_number and branch_name:
                add_device_branch_mapping(serial_number, branch_name)
                flash(f"Device mapping added: {serial_number} → {branch_name}", "success")
            else:
                flash("Serial number and branch name are required.", "error")

        elif action == "designation":
            # Handle designation mapping
            employee_id = request.form.get("employee_id")
            designation = request.form.get("designation")

            if employee_id and designation:
                try:
                    add_employee_designation_mapping(employee_id, designation)
                    flash(f"Designation mapping added: {employee_id} → {designation}", "success")
                except ValueError as e:
                    flash(f"Error adding designation for {employee_id}: {e!s}", "error")
                except Exception as e:
                    flash(f"Unexpected error adding designation for {employee_id}: {e!s}", "error")
            else:
                flash("Employee ID and designation are required.", "error")

        elif action == "employee_name":
            # Handle employee name mapping
            employee_id = request.form.get("employee_id")
            employee_name = request.form.get("employee_name")

            if employee_id and employee_name:
                try:
                    add_employee_name_mapping(employee_id, employee_name)
                    flash(f"Employee name mapping added: {employee_id} → {employee_name}", "success")
                except ValueError as e:
                    flash(f"Error adding employee name for {employee_id}: {e!s}", "error")
                except Exception as e:
                    flash(f"Unexpected error adding employee name for {employee_id}: {e!s}", "error")
            else:
                flash("Employee ID and employee name are required.", "error")

        return redirect(url_for("employee.unified_management"))

    # Get data for display
    employees = get_comprehensive_employee_data() or []
    shift_templates = get_shift_templates() or []
    device_mappings = get_device_branch_mappings() or []
    all_branches = sorted({b["branch_name"] for b in device_mappings})
    all_designations = sorted({d["designation"] for d in get_employee_designation_mappings() or []})
    all_employee_names = sorted({n["employee_name"] for n in get_employee_name_mappings() or []})
    # Provide a fallback lookup of employee_id -> employee_name so templates can display names when missing
    try:
        name_mappings = get_employee_name_mappings() or []
        employee_id_to_name = {str(n.get("employee_id")): n.get("employee_name", "") for n in name_mappings if n.get("employee_id")}
    except Exception:
        employee_id_to_name = {}

    return render_template(
        "unified_management.html",
        employees=employees,
        shift_templates=shift_templates,
        device_mappings=device_mappings,
        all_branches=all_branches,
        all_designations=all_designations,
        all_employee_names=all_employee_names,
        employee_id_to_name=employee_id_to_name,
    )


@router.route("/download_employee_template")
def download_employee_template() -> Any:
    """Download CSV template for bulk employee upload."""
    template_data = [
        {"EMP No": "001", "EMP Name": "John Smith", "Designation": "Manager"},
        {"EMP No": "002", "EMP Name": "Jane Doe", "Designation": "Developer"},
        {"EMP No": "003", "EMP Name": "Bob Johnson", "Designation": "Analyst"},
    ]

    df = pd.DataFrame(template_data)

    with tempfile.NamedTemporaryFile(suffix=".csv", delete=False, mode="w", newline="", encoding="utf-8") as tmp:
        df.to_csv(tmp, index=False)
        tmp_path = tmp.name

    return send_file(tmp_path, as_attachment=True, download_name="employee_bulk_upload_template.csv", mimetype="text/csv")


@router.route("/bulk_employee_upload", methods=["GET", "POST"])
def bulk_employee_upload() -> Any:
    """Handle bulk employee upload from CSV file."""
    if request.method == "POST":
        # Require selected branch from dropdown
        selected_branch = request.form.get("selected_branch", "").strip()
        if not selected_branch:
            flash("Please select a branch before uploading.", "error")
            return redirect(url_for("employee.bulk_employee_upload"))

        # Check if file was uploaded
        if "file" not in request.files:
            flash("No file selected. Please choose a CSV file to upload.", "error")
            return redirect(url_for("employee.bulk_employee_upload"))

        file = request.files["file"]

        # Check if file has a name
        if file.filename == "":
            flash("No file selected. Please choose a CSV file to upload.", "error")
            return redirect(url_for("employee.bulk_employee_upload"))

        # Check if file is CSV format
        if not file.filename.lower().endswith(".csv"):
            flash("Invalid file format. Please upload a CSV file (.csv).", "error")
            return redirect(url_for("employee.bulk_employee_upload"))

        try:
            # Read CSV file
            df = pd.read_csv(file)

            # Validate required columns
            required_columns = ["EMP No", "EMP Name", "Designation"]  # Branch removed; chosen via dropdown
            missing_columns = [col for col in required_columns if col not in df.columns]

            if missing_columns:
                flash(f"Missing required columns: {', '.join(missing_columns)}. Please use the template file.", "error")
                return redirect(url_for("employee.bulk_employee_upload"))

            # Build existing name map (name -> employee_id) to enforce unique employee names
            existing_name_mappings = get_employee_name_mappings() or []
            name_map = {str(n.get("employee_name", "")).strip().lower(): str(n.get("employee_id")) for n in existing_name_mappings if n.get("employee_name")}

            # Process each row
            success_count = 0
            error_count = 0
            errors = []

            for index, row in df.iterrows():
                try:
                    emp_no = str(row["EMP No"]).strip()
                    emp_name = str(row["EMP Name"]).strip()
                    designation = str(row["Designation"]).strip()

                    # Validate required fields
                    if not emp_no or emp_no == "nan":
                        errors.append(f"Row {index + 2}: Employee Number is required")
                        error_count += 1
                        continue

                    if not emp_name or emp_name == "nan":
                        errors.append(f"Row {index + 2}: Employee Name is required")
                        error_count += 1
                        continue

                    if not designation or designation == "nan":
                        errors.append(f"Row {index + 2}: Designation is required")
                        error_count += 1
                        continue

                    # Check for duplicate employee name assigned to another employee
                    existing = name_map.get(emp_name.strip().lower()) if emp_name else None
                    if existing and existing != emp_no:
                        errors.append(f"Row {index + 2}: Employee Name '{emp_name}' already exists for employee ID {existing}")
                        error_count += 1
                        continue

                    # Upsert comprehensive employee (bulk upload should overwrite existing records)
                    # Get the default shift (will be used if provided shift is empty)
                    default_shift = get_default_shift()
                    # Use the selected branch for all rows
                    update_comprehensive_employee(emp_no, emp_name, designation, selected_branch, default_shift)
                    success_count += 1

                    # Update name map so subsequent rows in the same upload see the new mapping
                    if emp_name:
                        name_map[emp_name.strip().lower()] = emp_no

                except Exception as e:
                    errors.append(f"Row {index + 2}: {e!s}")
                    error_count += 1

            # Show results
            if success_count > 0:
                flash(f"Successfully added {success_count} employee(s) to branch '{selected_branch}'.", "success")
                default_shift = get_default_shift()
                if default_shift:
                    flash(f"All employees were assigned the default shift: {default_shift}", "success")
                else:
                    flash("Warning: No default shift was set. Please configure a default shift in Settings.", "error")

            if error_count > 0:
                flash(f"Failed to add {error_count} employee(s). Errors:", "error")
                for error in errors[:5]:  # Show first 5 errors
                    flash(error, "error")
                if len(errors) > 5:
                    flash(f"... and {len(errors) - 5} more errors.", "error")

        except Exception as e:
            flash(f"Error reading CSV file: {e!s}", "error")

        return redirect(url_for("employee.bulk_employee_upload"))

    # GET request - show upload form with available branches
    try:
        device_branches = {m.get("branch_name") for m in (get_device_branch_mappings() or []) if m.get("branch_name")}
        employee_branches = {m.get("branch_name") for m in (get_employee_branch_mappings() or []) if m.get("branch_name")}
        branches = sorted(device_branches | employee_branches)
    except Exception:
        branches = []

    return render_template("bulk_employee_upload.html", branches=branches)
