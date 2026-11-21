# -*- coding: utf-8 -*-
"""
Generate publication-grade 5-Tier Enterprise Testing Architecture Diagram
for KAFEINA ERP Academic Reports.
"""
import matplotlib.pyplot as plt
import matplotlib.patches as patches
from matplotlib.path import Path
import os

# Set font family
plt.rcParams['font.sans-serif'] = 'Arial'
plt.rcParams['font.family'] = 'sans-serif'

fig, ax = plt.subplots(figsize=(14, 9), dpi=300)
fig.patch.set_facecolor('#FFFFFF')
ax.set_facecolor('#FFFFFF')

# Diagram bounds
ax.set_xlim(0, 1400)
ax.set_ylim(0, 900)
ax.axis('off')

# Title & Subtitle Header
ax.text(700, 855, "ARSIKETUR PENGUJIAN LIMA LAPIS (5-TIER ENTERPRISE QUALITY FRAMEWORK)", 
        ha='center', va='center', fontsize=18, fontweight='bold', color='#0F172A')
ax.text(700, 825, "Implementasi Framework Pengujian Terpadu KAFEINA Specialty Roastery & Multi-Branch POS", 
        ha='center', va='center', fontsize=12, style='italic', color='#475569')

# Define tiers data
# Each tier has: name, tool, desc, color, y_bottom, y_top, w_top, w_bottom
tiers = [
    {
        "tier": "TIER 5",
        "name": "E2E BROWSER TESTING",
        "tool": "Laravel Dusk & Chrome Driver",
        "desc": "Verifikasi DOM Asli, Alpine.js, Interaktivitas Layar Sentuh POS, dan Modal Shift",
        "color_bg": "#EEF2FF",
        "color_border": "#6366F1",
        "color_bar": "#4F46E5",
        "w_bottom": 580,
        "w_top": 460,
        "y": 660,
        "h": 95
    },
    {
        "tier": "TIER 4",
        "name": "CORE FEATURE & DOMAIN INTEGRATION",
        "tool": "Pest PHP v4 & PHPUnit 12",
        "desc": "Alur Bisnis Rantai Pasok Terpadu, Isolasi DatabaseTransactions, 155 Kasus Uji (696 Asersi)",
        "color_bg": "#ECFDF5",
        "color_border": "#10B981",
        "color_bar": "#059669",
        "w_bottom": 720,
        "w_top": 600,
        "y": 545,
        "h": 95
    },
    {
        "tier": "TIER 3",
        "name": "STRUCTURAL WHITE-BOX TESTING",
        "tool": "McCabe Basis Path & Control Flow Graph (CFG)",
        "desc": "100% Branch Coverage, Evaluasi Mutu QC SCA, Formula Susut Sangrai & HPP Riil",
        "color_bg": "#F0FDFA",
        "color_border": "#14B8A6",
        "color_bar": "#0D9488",
        "w_bottom": 860,
        "w_top": 740,
        "y": 430,
        "h": 95
    },
    {
        "tier": "TIER 2",
        "name": "ARCHITECTURE COMPLIANCE GUARD",
        "tool": "Pest Architecture Testing Engine",
        "desc": "Pemisahan Controller-Service-Model, Larangan Fungsi Debug (dd/dump), Larangan env() di Luar Config",
        "color_bg": "#FFFBEB",
        "color_border": "#F59E0B",
        "color_bar": "#D97706",
        "w_bottom": 1000,
        "w_top": 880,
        "y": 315,
        "h": 95
    },
    {
        "tier": "TIER 1",
        "name": "STATIC TYPE & DEFECT ANALYSIS",
        "tool": "Larastan / PHPStan Level 5",
        "desc": "Zero Static Defect, Analisis Sintaksis 65 Kelas Sumber, Strict Typing, dan Null-Safety Objek",
        "color_bg": "#EFF6FF",
        "color_border": "#3B82F6",
        "color_bar": "#1D4ED8",
        "w_bottom": 1140,
        "w_top": 1020,
        "y": 200,
        "h": 95
    }
]

cx = 700

# Draw trapezoids / rounded cards for each tier
for t in tiers:
    y = t["y"]
    h = t["h"]
    wb = t["w_bottom"]
    wt = t["w_top"]
    
    # Vertices of trapezoid
    verts = [
        (cx - wb / 2, y),             # Bottom Left
        (cx + wb / 2, y),             # Bottom Right
        (cx + wt / 2, y + h),         # Top Right
        (cx - wt / 2, y + h),         # Top Left
        (cx - wb / 2, y)              # Close
    ]
    codes = [Path.MOVETO, Path.LINETO, Path.LINETO, Path.LINETO, Path.CLOSEPOLY]
    path = Path(verts, codes)
    patch = patches.PathPatch(path, facecolor=t["color_bg"], edgecolor=t["color_border"], lw=2.2, zorder=2)
    ax.add_patch(patch)
    
    # Left accent badge on the trapezoid
    # Badge text
    ax.text(cx, y + h - 24, f"{t['tier']} : {t['name']}", 
            ha='center', va='center', fontsize=12.5, fontweight='bold', color=t["color_bar"], zorder=4)
    
    # Tool tag (pill)
    pill_text = f"Alat: {t['tool']}"
    ax.text(cx, y + h - 50, pill_text,
            ha='center', va='center', fontsize=10.5, fontweight='bold', color='#1E293B', zorder=4)
    
    # Description
    ax.text(cx, y + h - 74, t["desc"],
            ha='center', va='center', fontsize=9.2, color='#475569', zorder=4)

# Left Side Indicator: Kecepatan & Biaya
# Arrow going down: Lebih Cepat / Lebih Murah
arrow_left = patches.FancyArrowPatch(
    (80, 755), (80, 200),
    connectionstyle="arc3,rad=0",
    color="#059669",
    lw=2.5,
    arrowstyle="-|>,head_length=10,head_width=6",
    zorder=3
)
ax.add_patch(arrow_left)
ax.text(95, 740, "WAKTU EKSEKUSI LEBIH LAMA\n(Menit / Real Browser)", ha='left', va='center', fontsize=8.5, fontweight='bold', color='#0F172A')
ax.text(95, 215, "UMPAN BALIK INSTAN\n(Detik / Fast Static Loop)", ha='left', va='center', fontsize=8.5, fontweight='bold', color='#059669')
ax.text(65, 480, "FREKUENSI EKSEKUSI & KECEPATAN (SPEED)", ha='center', va='center', fontsize=9, fontweight='bold', color='#059669', rotation=90)

# Right Side Indicator: Cakupan & Kedalaman Kode
# Arrow going up: Lingkup Sistem Menyeluruh
arrow_right = patches.FancyArrowPatch(
    (1320, 200), (1320, 755),
    connectionstyle="arc3,rad=0",
    color="#2563EB",
    lw=2.5,
    arrowstyle="-|>,head_length=10,head_width=6",
    zorder=3
)
ax.add_patch(arrow_right)
ax.text(1305, 740, "TINGKAT SISTEM & INTEGRASI\n(End-to-End User Experience)", ha='right', va='center', fontsize=8.5, fontweight='bold', color='#2563EB')
ax.text(1305, 215, "TINGKAT KODE & STRUKTUR\n(Unit, Types, AST & Syntax)", ha='right', va='center', fontsize=8.5, fontweight='bold', color='#0F172A')
ax.text(1335, 480, "LINGKUP CAKUPAN PENGUJIAN (SCOPE)", ha='center', va='center', fontsize=9, fontweight='bold', color='#2563EB', rotation=-90)

# Footer Summary Box
footer_rect = patches.FancyBboxPatch(
    (130, 75), 1140, 75,
    boxstyle="round,pad=8,rounding_size=12",
    facecolor="#F8FAFC",
    edgecolor="#CBD5E1",
    lw=1.5,
    zorder=2
)
ax.add_patch(footer_rect)

ax.text(700, 122, "RINGKASAN METRIK ARSITEKTUR MULTI-TIER KAFEINA ERP", 
        ha='center', va='center', fontsize=11, fontweight='bold', color='#0F172A')
ax.text(700, 96, "Total: 164 Kasus Uji Terpadu | 710+ Asersi Komprehensif | Durasi Total: 68.1 Detik | Pass Rate: 100% (Zero Defect)", 
        ha='center', va='center', fontsize=10, fontweight='bold', color='#059669')

output_path = r"d:\Kafeina\docs\assets\diagram_5_tier_testing_architecture.png"
os.makedirs(os.path.dirname(output_path), exist_ok=True)
plt.tight_layout()
plt.savefig(output_path, dpi=300, facecolor=fig.get_facecolor(), edgecolor='none', bbox_inches='tight')
plt.close()
print(f"[SUCCESS] Diagram 5-Tier Testing generated at: {output_path}")
