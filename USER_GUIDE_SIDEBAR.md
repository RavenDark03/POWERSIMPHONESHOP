# User Guide - New Sidebar Navigation

## Overview
The Pawnshop admin system now features a modern left-side navigation sidebar with icons and smooth animations. This guide will help you navigate and use the new interface.

## Sidebar Layout

```
┌─────────────────────────────────────┐
│ [LOGO] Powersim    [◀─ Toggle Btn] │  ← Header with Logo & Toggle
├─────────────────────────────────────┤
│ 📊 Dashboard                        │
│ 👥 Customers                        │
│ 💍 Pawning                          │ ← Navigation Items with Icons
│ 📦 Inventory                        │
│ 📄 Reports                          │
│ 👔 Users                            │
├─────────────────────────────────────┤
│ 🚪 Logout                           │  ← Logout Button
└─────────────────────────────────────┘
```

## Features

### 1. **Collapsible Sidebar**
- **Full View**: 280px width with labels visible
- **Collapsed View**: 80px width with icons only
- **Toggle Button**: Click the chevron (◀) at the top right to collapse/expand
- **State Memory**: Your preference is saved automatically

### 2. **Navigation Items**

| Icon | Label | Purpose |
|------|-------|---------|
| 📊 | Dashboard | Main admin dashboard with charts |
| 👥 | Customers | Add, edit, view customer records |
| 💍 | Pawning | Manage pawn transactions |
| 📦 | Inventory | Track inventory status |
| 📄 | Reports | View business analytics |
| 👔 | Users | Manage staff accounts |

### 3. **Active Page Indicator**
- Current page link is highlighted in **gold color**
- A gold bar appears on the left edge
- Helps you know where you are

### 4. **Interactive Effects**

#### Hover State
- Background becomes semi-transparent gold
- Text brightens to white
- Smooth 0.3s animation
- Left border animates in

#### Active State
- Brighter gold background
- Gold text color
- Gold left border visible
- Current page indication

#### Collapsed Mode
- Icons only (larger, easier to click)
- Labels fade out
- All icons remain visible
- Smooth 0.3s transition

## How to Use

### Opening a Section
1. Click any icon or label in the sidebar
2. Page will load instantly
3. Sidebar highlights the active section

### Related Pages
When viewing related content, the main category stays highlighted:
- View/Edit Customer → **Customers** section stays active
- New/View/Redeem Pawn → **Pawning** section stays active
- View User Account → **Users** section stays active

### Collapsing for More Space
1. Click the toggle button (◀) at the top right of sidebar
2. Sidebar shrinks to icon-only mode
3. Content area expands for more workspace
4. Your preference is remembered next visit

### Logging Out
1. Click the **Logout** button at bottom of sidebar
2. Confirmation dialog appears
3. Click OK to confirm logout
4. You'll be returned to login page

## Design Features

### Modern Glassmorphism Effect
- Frosted glass appearance with blur effect
- Semi-transparent backgrounds
- Gold accent borders
- Smooth shadows for depth

### Responsive Design
- Works on desktop, tablet, and mobile
- On small screens, sidebar can be toggled off-screen
- Touch-friendly button sizes
- Clear, readable text

### Professional Appearance
- Gradient background using brand colors
- Consistent with company branding
- Clean, organized layout
- Premium feel

## Keyboard Shortcuts
- **No keyboard shortcuts** currently configured
- Future versions may include Alt+S for sidebar toggle

## Mobile Optimization
- On tablets/phones, sidebar takes full height
- Icons remain visible and clickable
- Swipe to close feature (future update)
- Landscape mode shows full labels

## Troubleshooting

### Sidebar Not Appearing?
- Make sure you're logged in as admin
- Clear browser cache and refresh
- Check if JavaScript is enabled

### Collapsed Sidebar Won't Expand?
- Click the chevron button (◀) again
- It should toggle back to full width
- If stuck, clear localStorage and refresh

### Icons Not Showing?
- Font Awesome icons may not have loaded
- Check internet connection
- Refresh the page
- Check browser console for errors

### Wrong Active Highlight?
- This is intentional for related pages
- For example, "View Customer" keeps "Customers" highlighted
- Helps you see which section you're working in

## Tips & Tricks

1. **Save Space**: Collapse sidebar when working on forms
2. **Quick Navigation**: Use icons to quickly spot sections
3. **Remember Preference**: Your collapse preference is saved
4. **Accessibility**: Hover over items to see full labels even when collapsed

## Browser Compatibility

| Browser | Support | Notes |
|---------|---------|-------|
| Chrome | ✅ Full | Best performance |
| Firefox | ✅ Full | Smooth animations |
| Safari | ✅ Full | Perfect animations |
| Edge | ✅ Full | Like Chrome |
| IE11 | ❌ Limited | Not recommended |

## Accessibility

- Clear color contrast (gold text on dark background)
- Large touch targets for mobile users
- Icon + text labels help recognition
- Smooth animations don't cause motion sickness
- Keyboard navigation supported

## Future Enhancements

Planned improvements for the next update:
- ✨ Dark mode toggle
- ✨ Custom color themes
- ✨ Submenu support for nested navigation
- ✨ Search functionality
- ✨ Keyboard shortcuts
- ✨ Mobile hamburger menu overlay

## Support

For technical issues:
1. Refresh the page (Ctrl+R)
2. Clear cache (Ctrl+Shift+Delete)
3. Check browser console (F12)
4. Contact administrator if problem persists

---

**Version**: 2.0  
**Release Date**: January 2026  
**Last Updated**: January 17, 2026
