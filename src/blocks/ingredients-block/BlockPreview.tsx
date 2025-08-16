export default function BlockPreview( { attributes } ) {
	const {
		name,
		quantityVolume,
		unitVolume,
		quantityWeight,
		unitWeight,
		description,
	} = attributes;
	const hasWeight = 0 !== quantityWeight;
	const hasVolume = 0 !== quantityVolume;
	return (
		<>
			{ hasVolume && (
				<span>
					{ quantityVolume } { unitVolume }
				</span>
			) }
			{ hasWeight && hasVolume ? (
				<span>
					({ quantityWeight } { unitWeight })
				</span>
			) : (
				<span>
					{ quantityWeight } { unitWeight }
				</span>
			) }
			<span>{ name }</span>
			{ description && <span>{ description }</span> }
		</>
	);
}
