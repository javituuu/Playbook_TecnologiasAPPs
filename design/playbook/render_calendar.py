"""Render the three Playbook sign-in illustrations.

blender -b -t 8 --python-exit-code 1 --python render_calendar.py -- OUTPUT [--preview]
"""

import argparse
import sys
from pathlib import Path

import bpy
from mathutils import Vector


PALETTE = {
    "grape": "#4B4E6D",
    "aqua": "#84DCC6",
    "steel": "#95A3B3",
}
# Cada composición se lee como un fragmento del horario.
COMPOSITIONS = {
    "jugador": ((5.8, -7.5, 10), [(0, 0), (1, 0), (1, 1), (2, 1), (3, 2)], {(1, 1), (3, 2)}),
    "coach": ((-5.8, -7.5, 10), [(0, 1), (1, 0), (1, 1), (2, 1), (2, 2), (3, 1)], {(1, 1), (2, 1)}),
    "panel": ((5.8, -7.5, 11), [(0, 0), (1, 0), (2, 0), (1, 2), (2, 2), (3, 2)], {(1, 0), (2, 2)}),
}

def material(name, hex_color, roughness):
    rgb = (int(hex_color[i:i + 2], 16) / 255 for i in (1, 3, 5))
    linear = tuple(x / 12.92 if x < 0.04045 else ((x + 0.055) / 1.055) ** 2.4 for x in rgb)
    mat = bpy.data.materials.new(name)
    mat.diffuse_color = (*linear, 1)
    mat.use_nodes = True
    shader = mat.node_tree.nodes.get("Principled BSDF")
    shader.inputs["Base Color"].default_value = (*linear, 1)
    shader.inputs["Roughness"].default_value = roughness
    return mat


def block(name, location, size, radius, surface):
    bpy.ops.mesh.primitive_cube_add(size=1, location=location)
    obj = bpy.context.object
    obj.name = name
    obj.dimensions = size
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
    bevel = obj.modifiers.new("Bordes fresados", "BEVEL")
    bevel.width = radius
    bevel.segments = 4
    normals = obj.modifiers.new("Normales de las caras", "WEIGHTED_NORMAL")
    normals.keep_sharp = True
    obj.data.materials.append(surface)
    return obj


def aim(obj, target):
    obj.rotation_euler = (Vector(target) - obj.location).to_track_quat("-Z", "Y").to_euler()


def softbox(name, location, energy, size, tint):
    bpy.ops.object.light_add(type="AREA", location=location)
    light = bpy.context.object
    light.name = name
    light.data.energy = energy
    light.data.shape = "DISK"
    light.data.size = size
    light.data.color = tint
    aim(light, (0, 0, 0))


def build(role, preview):
    camera, slots, marked = COMPOSITIONS[role]
    bpy.ops.wm.read_factory_settings(use_empty=True)
    scene = bpy.context.scene
    scene.render.engine = "CYCLES"
    scene.cycles.device = "CPU"
    scene.cycles.samples = 12 if preview else 32
    scene.cycles.use_denoising = True
    scene.render.threads_mode = "FIXED"
    scene.render.threads = 8
    scene.render.resolution_x = 900
    scene.render.resolution_y = 720
    scene.render.resolution_percentage = 40 if preview else 100
    scene.render.film_transparent = True
    scene.render.image_settings.file_format = "PNG"
    scene.render.image_settings.color_mode = "RGBA"
    scene.view_settings.view_transform = "AgX"
    scene.world = bpy.data.worlds.new("Estudio de Playbook")
    scene.world.use_nodes = True
    scene.world.node_tree.nodes["Background"].inputs["Strength"].default_value = 0.27
    mats = {
        name: material(name, hex_color, 0.44 if name == "aqua" else 0.60)
        for name, hex_color in PALETTE.items()
    }
    for column, row in slots:
        x, y = (column - 1.5) * 1.16, (1 - row) * 1.04
        active = (column, row) in marked
        z = 0.18 if active else 0.0
        block(f"Bloque {column + 1}.{row + 1}", (x, y, z), (1.01, 0.89, 0.20),
              0.083, mats["aqua" if active else "steel"])
        if active:
            block("Ranura de agenda", (x, y, z + 0.10), (0.48, 0.032, 0.016),
                  0.006, mats["grape"])
    softbox("Principal", (-4.5, -5, 10), 1950, 7, (1.0, 0.96, 0.93))
    softbox("Relleno", (6, -1, 5), 1050, 8, (0.73, 0.83, 1))
    softbox("Contra", (1, 6, 6), 1700, 5, (0.75, 1, 0.91))
    bpy.ops.object.camera_add(location=camera)
    scene.camera = bpy.context.object
    scene.camera.name = "Cámara " + role
    scene.camera.data.type = "ORTHO"
    scene.camera.data.ortho_scale = 5.95
    aim(scene.camera, (0, 0, 0.09))
    return scene


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("output", type=Path)
    parser.add_argument("--preview", action="store_true")
    args = parser.parse_args(sys.argv[sys.argv.index("--") + 1:])
    args.output.mkdir(parents=True, exist_ok=True)
    roles = ("jugador",) if args.preview else COMPOSITIONS
    for role in roles:
        scene = build(role, args.preview)
        scene.render.filepath = str((args.output / f"playbook-{role}.png").resolve())
        if role == "jugador" and not args.preview:
            scene.render.filepath = "//playbook-jugador.png"
            bpy.ops.wm.save_as_mainfile(filepath=str((args.output / "playbook-calendar.blend").resolve()))
            scene.render.filepath = str((args.output / f"playbook-{role}.png").resolve())
        bpy.ops.render.render(write_still=True)


if __name__ == "__main__":
    main()
